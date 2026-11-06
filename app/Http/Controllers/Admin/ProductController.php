<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $products = Product::query()->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedProduct($request);
        $validated['slug'] = Product::createSlug($validated['title']);
        $validated['sku'] = Str::upper($validated['sku']);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['measurements'] = array_filter([
            'pit_to_pit' => $validated['pit_to_pit'] ?? null,
            'length' => $validated['length'] ?? null,
            'shoulder' => $validated['shoulder'] ?? null,
        ]);
        unset($validated['pit_to_pit'], $validated['length'], $validated['shoulder']);
        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): View
    {
        return view('storefront.product', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        return view('admin.products.form', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedProduct($request, $product);
        $validated['sku'] = Str::upper($validated['sku']);
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['measurements'] = array_filter([
            'pit_to_pit' => $validated['pit_to_pit'] ?? null,
            'length' => $validated['length'] ?? null,
            'shoulder' => $validated['shoulder'] ?? null,
        ]);
        unset($validated['pit_to_pit'], $validated['length'], $validated['shoulder']);
        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_published' => false, 'stock' => 0]);

        return redirect()->route('admin.products.index')->with('success', 'Produk diarsipkan. Riwayat pesanan tetap tersimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'string', 'max:30'],
            'condition_score' => ['nullable', 'numeric', 'between:0,10'],
            'condition_notes' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url', 'max:2000'],
            'era' => ['nullable', 'string', 'max:80'],
            'stock' => ['required', 'integer', 'min:0', 'max:999'],
            'pit_to_pit' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'shoulder' => ['nullable', 'numeric', 'min:0', 'max:300'],
        ]);
    }
}
