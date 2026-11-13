<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

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
        $photos = $this->syncPhotos($request);
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
        $validated['image_path'] = $photos['image_path'];
        $validated['gallery'] = $photos['gallery'];

        try {
            Product::create($validated);
        } catch (Throwable $exception) {
            $this->deletePhotoFiles($photos['stored']);

            throw $exception;
        }

        $this->deletePhotoFiles($photos['removed']);

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
        $photos = $this->syncPhotos($request, $product);
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
        $validated['image_path'] = $photos['image_path'];
        $validated['gallery'] = $photos['gallery'];

        try {
            $product->update($validated);
        } catch (Throwable $exception) {
            $this->deletePhotoFiles($photos['stored']);

            throw $exception;
        }

        $this->deletePhotoFiles($photos['removed']);

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
     * Persist the uploaded main photo and gallery, returning the model attributes
     * plus the files that should be cleaned up after a successful save.
     *
     * @return array{image_path: string|null, gallery: array<int, string>, stored: array<int, string>, removed: array<int, string>}
     */
    private function syncPhotos(Request $request, ?Product $product = null): array
    {
        $photos = $this->validatedPhotos($request);
        $disk = config('filesystems.product_photo_disk');
        $stored = [];
        $removed = [];
        $imagePath = $product?->image_path;
        $gallery = array_values(array_filter(
            $product?->gallery ?? [],
            fn (mixed $item): bool => is_string($item) && trim($item) !== '',
        ));

        if ($photos['image'] instanceof UploadedFile) {
            $imagePath = $photos['image']->store('products', $disk);
            $stored[] = $imagePath;

            if ($product?->image_path) {
                $removed[] = $product->image_path;
            }
        } elseif ($photos['remove_image']) {
            if ($product?->image_path) {
                $removed[] = $product->image_path;
            }

            $imagePath = null;
        }

        if ($product !== null && $photos['gallery_manage']) {
            $kept = array_values(array_intersect($gallery, $photos['gallery_keep']));
            $removed = array_merge($removed, array_values(array_diff($gallery, $kept)));
            $gallery = $kept;
        }

        foreach ($photos['gallery_files'] as $file) {
            $path = $file->store('products', $disk);
            $stored[] = $path;
            $gallery[] = $path;
        }

        foreach ($photos['gallery_urls'] as $url) {
            $gallery[] = trim($url);
        }

        $gallery = array_slice(array_values(array_unique($gallery)), 0, Product::MAX_GALLERY_PHOTOS);

        $retained = $imagePath === null ? $gallery : [...$gallery, $imagePath];

        $this->deletePhotoFiles(array_diff($stored, $retained));
        $stored = array_values(array_intersect($stored, $retained));

        return [
            'image_path' => $imagePath,
            'gallery' => $gallery,
            'stored' => $stored,
            'removed' => array_values(array_unique($removed)),
        ];
    }

    /**
     * @return array{image: UploadedFile|null, remove_image: bool, gallery_manage: bool, gallery_keep: array<int, string>, gallery_urls: array<int, string>, gallery_files: array<int, UploadedFile>}
     */
    private function validatedPhotos(Request $request): array
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'gallery_manage' => ['nullable', 'boolean'],
            'gallery_keep' => ['nullable', 'array'],
            'gallery_keep.*' => ['string', 'max:2000'],
            'gallery_urls' => ['nullable', 'array', 'max:'.Product::MAX_GALLERY_PHOTOS],
            'gallery_urls.*' => ['nullable', 'url', 'max:2000'],
            'gallery_files' => ['nullable', 'array', 'max:'.Product::MAX_GALLERY_PHOTOS],
            'gallery_files.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        return [
            'image' => $validated['image'] ?? null,
            'remove_image' => $request->boolean('remove_image'),
            'gallery_manage' => $request->boolean('gallery_manage'),
            'gallery_keep' => array_values($validated['gallery_keep'] ?? []),
            'gallery_urls' => array_values(array_filter(
                $validated['gallery_urls'] ?? [],
                fn (mixed $url): bool => is_string($url) && trim($url) !== '',
            )),
            'gallery_files' => array_values($validated['gallery_files'] ?? []),
        ];
    }

    /**
     * Delete only files this application stored on the product photo disk.
     *
     * @param  array<int, string|null>  $paths
     */
    private function deletePhotoFiles(array $paths): void
    {
        $paths = array_values(array_filter(
            $paths,
            fn (mixed $path): bool => is_string($path)
                && str_starts_with($path, 'products/')
                && ! str_starts_with($path, 'http://')
                && ! str_starts_with($path, 'https://'),
        ));

        if ($paths === []) {
            return;
        }

        Storage::disk(config('filesystems.product_photo_disk'))->delete($paths);
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
