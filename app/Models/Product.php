<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'sku', 'description', 'category', 'price', 'compare_at_price',
        'size', 'condition_score', 'condition_notes', 'image_url', 'gallery', 'era',
        'measurements', 'stock', 'is_featured', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'measurements' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'condition_score' => 'decimal:1',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
