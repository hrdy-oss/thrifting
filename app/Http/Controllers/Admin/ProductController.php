<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display the active inventory.
     */
    public function index(Request $request): View
    {
        return $this->productListing(
            $request,
            'admin.products.index',
            fn (Builder $query): Builder => $query->where('is_published', true),
        );
    }

    /**
     * Display the products marked as upcoming.
     */
    public function upcoming(Request $request): View
    {
        return $this->productListing(
            $request,
            'admin.products.upcoming',
            fn (Builder $query): Builder => $query->where('is_upcoming', true),
        );
    }

    /**
     * Display the archived (unpublished) products.
     */
    public function archived(Request $request): View
    {
        return $this->productListing(
            $request,
            'admin.products.archived',
            fn (Builder $query): Builder => $query->where('is_published', false),
        );
    }

    /**
     * Toggle a published product between the live catalog and the upcoming drop.
     */
    public function toggleUpcoming(Product $product): RedirectResponse
    {
        if (! $product->is_published) {
            return back()->with('error', 'Produk arsip tidak bisa ditandai upcoming. Pulihkan dulu.');
        }

        $product->update(['is_upcoming' => ! $product->is_upcoming]);

        return back()->with('success', $product->is_upcoming
            ? 'Produk ditandai sebagai upcoming.'
            : 'Produk dikeluarkan dari daftar upcoming.');
    }

    /**
     * Restore an archived product back into the inventory.
     */
    public function restore(Product $product): RedirectResponse
    {
        if ($product->is_published) {
            return back()->with('error', 'Produk ini sudah aktif.');
        }

        $stock = max(1, (int) $product->stock);
        $product->update(['is_published' => true, 'stock' => $stock]);

        return back()->with('success', 'Produk dipulihkan dengan stok '.$stock.'. Sesuaikan stok bila perlu.');
    }

    /**
     * Render one of the product listings with shared search, filter, and counters.
     *
     * @param  Closure(Builder): Builder  $scope
     */
    private function productListing(Request $request, string $view, Closure $scope): View
    {
        $products = $scope(Product::query())
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn (Builder $inner) => $inner->where('title', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->string('category')->toString()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Product::query()->distinct()->orderBy('category')->pluck('category');
        $counts = [
            'inventory' => Product::query()->where('is_published', true)->count(),
            'upcoming' => Product::query()->where('is_upcoming', true)->count(),
            'archived' => Product::query()->where('is_published', false)->count(),
        ];

        return view($view, compact('products', 'categories', 'counts'));
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
        $validated['is_upcoming'] = $request->boolean('is_upcoming');
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
        $validated['is_upcoming'] = $request->boolean('is_upcoming');
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
     * Archive the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_published' => false, 'is_upcoming' => false, 'stock' => 0]);

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
