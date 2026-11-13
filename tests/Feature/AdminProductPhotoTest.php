<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_a_main_photo_and_gallery_photos(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload([
                'image' => UploadedFile::fake()->image('utama.jpg'),
                'gallery_files' => [
                    UploadedFile::fake()->image('galeri-1.jpg'),
                    UploadedFile::fake()->image('galeri-2.jpeg'),
                ],
                'gallery_urls' => ['https://example.com/ekstra.jpg'],
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product = Product::firstOrFail();

        $this->assertNotNull($product->image_path);
        $this->assertCount(3, $product->gallery);
        Storage::disk('public')->assertExists($product->image_path);
        Storage::disk('public')->assertExists($product->gallery[0]);
        Storage::disk('public')->assertExists($product->gallery[1]);
        $this->assertSame('https://example.com/ekstra.jpg', $product->gallery[2]);
    }

    public function test_updating_a_product_can_keep_and_remove_gallery_photos(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);
        Storage::disk('public')->put('products/keep.jpg', 'keep');
        Storage::disk('public')->put('products/drop.jpg', 'drop');

        $product = $this->product([
            'gallery' => ['products/keep.jpg', 'products/drop.jpg'],
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => $product->sku,
                'gallery_manage' => '1',
                'gallery_keep' => ['products/keep.jpg'],
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame(['products/keep.jpg'], $product->gallery);
        Storage::disk('public')->assertExists('products/keep.jpg');
        Storage::disk('public')->assertMissing('products/drop.jpg');
    }

    public function test_replacing_the_main_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);
        Storage::disk('public')->put('products/lama.jpg', 'old');

        $product = $this->product(['image_path' => 'products/lama.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => $product->sku,
                'gallery_manage' => '1',
                'image' => UploadedFile::fake()->image('baru.jpg'),
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertNotSame('products/lama.jpg', $product->image_path);
        Storage::disk('public')->assertMissing('products/lama.jpg');
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_removing_the_main_photo_clears_the_path_and_deletes_the_file(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);
        Storage::disk('public')->put('products/hapus.jpg', 'old');

        $product = $this->product(['image_path' => 'products/hapus.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'sku' => $product->sku,
                'gallery_manage' => '1',
                'remove_image' => '1',
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertNull($product->image_path);
        Storage::disk('public')->assertMissing('products/hapus.jpg');
    }

    public function test_uploaded_photos_must_be_supported_image_files(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload([
                'image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
                'gallery_files' => [
                    UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'),
                ],
            ]))
            ->assertSessionHasErrors(['image', 'gallery_files.0']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_gallery_uploads_are_limited_to_six_files(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $files = array_map(
            fn (int $index): UploadedFile => UploadedFile::fake()->image("galeri-{$index}.jpg"),
            range(1, 7),
        );

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['gallery_files' => $files]))
            ->assertSessionHasErrors('gallery_files');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_non_admin_users_cannot_upload_product_photos(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post(route('admin.products.store'), $this->payload([
                'image' => UploadedFile::fake()->image('utama.jpg'),
            ]))
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_the_image_url_accessor_prefers_the_uploaded_photo(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $uploaded = new Product([
            'image_path' => 'products/utama.jpg',
            'image_url' => 'https://example.com/utama.jpg',
        ]);
        $externalOnly = new Product(['image_url' => 'https://example.com/utama.jpg']);

        $this->assertSame(Storage::disk('public')->url('products/utama.jpg'), $uploaded->image_url);
        $this->assertSame('https://example.com/utama.jpg', $externalOnly->image_url);
    }

    public function test_gallery_items_resolve_stored_paths_and_external_urls(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $product = new Product([
            'gallery' => ['products/galeri.jpg', 'https://example.com/ekstra.jpg'],
        ]);

        $this->assertSame([
            ['path' => 'products/galeri.jpg', 'url' => Storage::disk('public')->url('products/galeri.jpg')],
            ['path' => 'https://example.com/ekstra.jpg', 'url' => 'https://example.com/ekstra.jpg'],
        ], $product->galleryItems());
    }

    public function test_the_admin_product_form_accepts_photo_uploads_and_camera_capture(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false)
            ->assertSee('name="gallery_files[]"', false)
            ->assertSee('data-camera-open', false)
            ->assertSee('data-camera-modal', false);
    }

    public function test_the_edit_form_lists_existing_gallery_photos(): void
    {
        Storage::fake('public');
        config(['filesystems.product_photo_disk' => 'public']);

        $product = $this->product(['gallery' => ['https://example.com/lama.jpg']]);

        $this->actingAs($this->admin())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('https://example.com/lama.jpg', false)
            ->assertSee('name="gallery_keep[]"', false);
    }

    public function test_the_storefront_shows_gallery_thumbnails(): void
    {
        $product = $this->product([
            'gallery' => ['https://example.com/extra-1.jpg', 'https://example.com/extra-2.jpg'],
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-product-gallery-thumb', false)
            ->assertSee('https://example.com/extra-1.jpg', false)
            ->assertSee('https://example.com/extra-2.jpg', false);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): Product
    {
        return Product::create([
            'title' => 'Court Runner Low',
            'slug' => 'court-runner-low',
            'sku' => 'NEON-0001',
            'description' => 'White leather low-top in good condition.',
            'category' => 'shoes',
            'price' => 1299000,
            'stock' => 1,
            'is_published' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function payload(array $attributes = []): array
    {
        return [
            'title' => 'Court Runner Low',
            'sku' => 'NEON-0001',
            'description' => 'White leather low-top in good condition.',
            'category' => 'shoes',
            'price' => 1299000,
            'stock' => 1,
            'is_published' => '1',
            ...$attributes,
        ];
    }
}
