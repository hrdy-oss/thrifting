<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $cart = collect(session('cart', []));
        $products = Product::query()->whereIn('id', $cart->keys())->get()->keyBy('id');
        $subtotal = $products->sum('price');

        return view('storefront.cart', compact('products', 'subtotal'));
    }

    public function store(Product $product): RedirectResponse
    {
        if (! $product->is_published || $product->stock < 1) {
            return back()->withErrors(['cart' => 'Barang ini sudah tidak tersedia.']);
        }

        $cart = session('cart', []);
        $cart[$product->id] = 1;
        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Barang ditambahkan ke keranjang.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Barang dihapus dari keranjang.');
    }
}
