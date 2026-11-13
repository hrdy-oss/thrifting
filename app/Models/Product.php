<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * Maximum number of gallery photos kept per product.
     */
    public const MAX_GALLERY_PHOTOS = 6;

    protected $fillable = [
        'title', 'slug', 'sku', 'description', 'category', 'price', 'compare_at_price',
        'size', 'condition_score', 'condition_notes', 'image_url', 'image_path', 'gallery', 'era',
        'measurements', 'stock', 'is_featured', 'is_published', 'is_upcoming',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'measurements' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'is_upcoming' => 'boolean',
            'condition_score' => 'decimal:1',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Resolve the primary photo, preferring an uploaded file over an external URL.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $this->image_path
                ? self::photoDisk()->url($this->image_path)
                : $value,
        );
    }

    /**
     * Resolve the stored gallery entries into paths and public URLs.
     *
     * @return array<int, array{path: string, url: string}>
     */
    public function galleryItems(): array
    {
        return collect($this->gallery ?? [])
            ->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): array => [
                'path' => $item,
                'url' => str_starts_with($item, 'http://') || str_starts_with($item, 'https://')
                    ? $item
                    : self::photoDisk()->url($item),
            ])
            ->values()
            ->all();
    }

    /**
     * Get the disk used for product photos.
     */
    public static function photoDisk(): Filesystem
    {
        return Storage::disk(config('filesystems.product_photo_disk'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function createSlug(string $title): string
    {
        return Str::slug($title).'-'.Str::lower(Str::random(5));
    }
}
