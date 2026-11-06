<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $cart = collect(session('cart', []));
        if ($cart->isEmpty()) {
            return redirect()->route('products.index')->with('error', 'Keranjang kamu masih kosong.');
        }

        $products = Product::query()->whereIn('id', $cart->keys())->get()->keyBy('id');
        $subtotal = $products->sum('price');

        return view('storefront.checkout', compact('products', 'subtotal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:12'],
            'courier' => ['required', 'in:JNE,SiCepat'],
        ]);
        $cart = collect(session('cart', []));
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang kamu masih kosong.');
        }

        $order = DB::transaction(function () use ($cart, $validated): Order {
            $products = Product::query()->whereIn('id', $cart->keys())->lockForUpdate()->get();
            if ($products->count() !== $cart->count() || $products->contains(fn (Product $product): bool => ! $product->is_published || $product->stock < 1)) {
                throw ValidationException::withMessages(['cart' => 'Ada barang yang sudah tidak tersedia. Periksa kembali keranjang kamu.']);
            }

            $subtotal = $products->sum('price');
            $shippingCost = $validated['courier'] === 'JNE' ? 25000 : 29000;
            $order = Order::create([
                ...$validated,
                'order_number' => 'NEON-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'tracking_token' => (string) Str::uuid(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal + $shippingCost,
                'status' => 'awaiting_payment',
            ]);

            foreach ($products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'product_sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => 1,
                ]);
                $product->decrement('stock');
            }

            OrderEvent::create([
                'order_id' => $order->id,
                'status' => 'awaiting_payment',
                'description' => 'Pesanan dibuat. Pembayaran belum dikonfigurasi.',
            ]);

            return $order;
        });

        session()->forget('cart');

        return redirect()->route('orders.track', $order->tracking_token)
            ->with('success', 'Pesanan berhasil dibuat. Simpan tautan ini untuk memeriksa statusnya.');
    }
}
