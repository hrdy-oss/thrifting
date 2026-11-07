<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $products = Product::query()->where('is_published', true)->where('stock', '>', 0)->latest()->limit(8)->get();
        $featuredProducts = Product::query()->where('is_published', true)->where('stock', '>', 0)
            ->where('is_featured', true)->latest()->limit(4)->get();
        $categories = $this->categoryTiles();

        return view('storefront.home', compact('products', 'featuredProducts', 'categories'));
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

    /**
     * Homepage shop-by-category tiles, each resolved to an optional background photo.
     *
     * @return array<int, array{slug: string, label: string, image: string|null}>
     */
    private function categoryTiles(): array
    {
        $tiles = [
            ['hoodies', 'Hoodies'],
            ['t-shirts', 'T-Shirts'],
            ['pants', 'Pants'],
            ['jackets', 'Jackets'],
            ['shoes', 'Shoes'],
            ['topi', 'Topi'],
        ];

        return array_map(function (array $tile): array {
            [$slug, $label] = $tile;

            return [
                'slug' => $slug,
                'label' => $label,
                'image' => $this->categoryImage($slug),
            ];
        }, $tiles);
    }

    /**
     * Photo filename alternatives for category slugs whose image file is not named after the slug.
     *
     * @var array<string, array<int, string>>
     */
    private const CATEGORY_IMAGE_ALIASES = [
        'topi' => ['hat'],
    ];

    /**
     * Supported photo extensions inside public/images/categories, keyed by lowercase name.
     *
     * @var array<int, string>
     */
    private const CATEGORY_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * First matching photo in public/images/categories for a category slug, e.g. shoes.jpg.
     * Matching ignores filename case, so Pants.jpg resolves the pants slug.
     */
    private function categoryImage(string $slug): ?string
    {
        $directory = public_path('images/categories');

        if (! File::isDirectory($directory)) {
            return null;
        }

        $photos = [];

        foreach (File::files($directory) as $file) {
            $extension = Str::lower($file->getExtension());

            if (in_array($extension, self::CATEGORY_IMAGE_EXTENSIONS, true)) {
                $photos[Str::lower(Str::beforeLast($file->getFilename(), '.'))] = 'images/categories/'.$file->getFilename();
            }
        }

        foreach ($this->categoryImageNames($slug) as $name) {
            if (isset($photos[Str::lower($name)])) {
                return $photos[Str::lower($name)];
            }
        }

        return null;
    }

    /**
     * Filename candidates for a category slug: aliases first, then the slug and its singular form.
     *
     * @return array<int, string>
     */
    private function categoryImageNames(string $slug): array
    {
        return array_values(array_unique([
            ...(self::CATEGORY_IMAGE_ALIASES[$slug] ?? []),
            $slug,
            Str::singular($slug),
        ]));
    }
}
