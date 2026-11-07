<?php

namespace Tests\Feature;

use App\Http\Controllers\StorefrontController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_a_category_photo_regardless_of_filename_case(): void
    {
        $path = public_path('images/categories/NeonTestTile.jpg');
        file_put_contents($path, '');

        try {
            $this->assertSame('images/categories/NeonTestTile.jpg', $this->resolveCategoryImage('neontesttile'));
        } finally {
            unlink($path);
        }
    }

    public function test_it_returns_null_for_a_category_without_a_photo(): void
    {
        $this->assertNull($this->resolveCategoryImage('missing-category-photo'));
    }

    public function test_homepage_renders_a_category_tile_background_from_its_photo(): void
    {
        $path = public_path('images/categories/jackets.jpg');
        file_put_contents($path, '');

        try {
            $this->get(route('home'))
                ->assertOk()
                ->assertSee('images/categories/jackets.jpg', false);
        } finally {
            unlink($path);
        }
    }

    private function resolveCategoryImage(string $slug): ?string
    {
        $method = new \ReflectionMethod(StorefrontController::class, 'categoryImage');

        return $method->invoke(new StorefrontController, $slug);
    }
}
