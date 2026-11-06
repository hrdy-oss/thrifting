<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()->withCount('items')->latest()->paginate(15);
        $statuses = ['awaiting_payment', 'processing', 'shipped', 'delivered', 'cancelled'];

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['awaiting_payment', 'processing', 'shipped', 'delivered', 'cancelled'])],
            'courier' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);
            $allowedTransitions = [
                'awaiting_payment' => ['awaiting_payment', 'processing', 'cancelled'],
                'processing' => ['processing', 'shipped', 'cancelled'],
                'shipped' => ['shipped', 'delivered'],
                'delivered' => ['delivered'],
                'cancelled' => ['cancelled'],
            ];
            if (! in_array($validated['status'], $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                abort(422, 'Perubahan status pesanan tidak diizinkan.');
            }

            if ($validated['status'] === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                foreach ($lockedOrder->items as $item) {
                    if ($item->product_id) {
                        $item->product()->increment('stock', $item->quantity);
                    }
                }
            }

            $lockedOrder->update($validated);
            OrderEvent::create([
                'order_id' => $lockedOrder->id,
                'status' => $validated['status'],
                'description' => 'Status pesanan diperbarui oleh admin.',
            ]);
        });

        return back()->with('success', 'Status pesanan diperbarui.');
    }
}
