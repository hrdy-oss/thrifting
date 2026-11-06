<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->where('is_published', true)->where('stock', '>', 0)->latest()->limit(8)->get();
        $featuredProducts = Product::query()->where('is_published', true)->where('stock', '>', 0)
            ->where('is_featured', true)->latest()->limit(4)->get();

        return view('storefront.home', compact('products', 'featuredProducts'));
    }

    public function catalog(Request $request): View
    {
        $products = Product::query()->where('is_published', true)->where('stock', '>', 0)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest()->paginate(12)->withQueryString();
        $categories = Product::query()->where('is_published', true)->distinct()->orderBy('category')->pluck('category');

        return view('storefront.catalog', compact('products', 'categories'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_published && $product->stock > 0, 404);
        $relatedProducts = Product::query()->where('is_published', true)->where('stock', '>', 0)
            ->where('category', $product->category)->whereKeyNot($product->getKey())->limit(4)->get();

        return view('storefront.product', compact('product', 'relatedProducts'));
    }
}
