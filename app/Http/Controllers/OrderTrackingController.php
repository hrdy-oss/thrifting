<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OrderTrackingController extends Controller
{
    public function show(Request $request, string $token, MidtransService $midtrans): View
    {
        $order = Order::query()->with(['items', 'events' => fn ($query) => $query->oldest()])
            ->where('tracking_token', $token)->firstOrFail();

        // When Midtrans redirects the customer back after payment, refresh the
        // status from the API so the page is accurate even without a webhook.
        if ($midtrans->isConfigured()
            && $order->payment_status !== 'paid'
            && $request->filled('status_code')
            && $request->query('order_id') === $order->order_number) {
            try {
                $midtrans->syncStatus($order);
                $order = $order->fresh(['items', 'events' => fn ($query) => $query->oldest()]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return view('storefront.order', compact('order'));
    }
}
