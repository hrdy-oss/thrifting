<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_lists_active_products_and_excludes_archived_ones(): void
    {
        $live = $this->product(['title' => 'Live Piece', 'slug' => 'live-piece', 'sku' => 'NEON-01']);
        $upcoming = $this->product(['title' => 'Upcoming Piece', 'slug' => 'upcoming-piece', 'sku' => 'NEON-02', 'is_upcoming' => true]);
        $archived = $this->product(['title' => 'Archived Piece', 'slug' => 'archived-piece', 'sku' => 'NEON-03', 'is_published' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee($live->title)
            ->assertSee($upcoming->title)
            ->assertDontSee($archived->title);
    }

    public function test_upcoming_page_lists_only_upcoming_products(): void
    {
        $live = $this->product(['title' => 'Live Piece', 'slug' => 'live-piece', 'sku' => 'NEON-01']);
        $upcoming = $this->product(['title' => 'Upcoming Piece', 'slug' => 'upcoming-piece', 'sku' => 'NEON-02', 'is_upcoming' => true]);
        $archived = $this->product(['title' => 'Archived Piece', 'slug' => 'archived-piece', 'sku' => 'NEON-03', 'is_published' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.products.upcoming'))
            ->assertOk()
            ->assertSee($upcoming->title)
            ->assertDontSee($live->title)
            ->assertDontSee($archived->title);
    }

    public function test_archived_page_lists_only_unpublished_products(): void
    {
        $live = $this->product(['title' => 'Live Piece', 'slug' => 'live-piece', 'sku' => 'NEON-01']);
        $upcoming = $this->product(['title' => 'Upcoming Piece', 'slug' => 'upcoming-piece', 'sku' => 'NEON-02', 'is_upcoming' => true]);
        $archived = $this->product(['title' => 'Archived Piece', 'slug' => 'archived-piece', 'sku' => 'NEON-03', 'is_published' => false]);

        $this->actingAs($this->admin())
            ->get(route('admin.products.archived'))
            ->assertOk()
            ->assertSee($archived->title)
            ->assertDontSee($live->title)
            ->assertDontSee($upcoming->title);
    }

    public function test_inventory_can_be_searched_and_filtered_by_category(): void
    {
        $hoodie = $this->product(['title' => 'Dark Dreams Hoodie', 'slug' => 'dark-dreams-hoodie', 'sku' => 'NEON-01', 'category' => 'hoodies']);
        $shoes = $this->product(['title' => 'Court Runner Low', 'slug' => 'court-runner-low', 'sku' => 'NEON-02', 'category' => 'shoes']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['q' => 'Runner']))
            ->assertOk()
            ->assertSee($shoes->title)
            ->assertDontSee($hoodie->title);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['category' => 'hoodies']))
            ->assertOk()
            ->assertSee($hoodie->title)
            ->assertDontSee($shoes->title);
    }

    public function test_admin_can_toggle_a_product_as_upcoming(): void
    {
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.products.toggle-upcoming', $product))
            ->assertRedirect();

        $this->assertTrue($product->fresh()->is_upcoming);

        $this->actingAs($admin)
            ->patch(route('admin.products.toggle-upcoming', $product))
            ->assertRedirect();

        $this->assertFalse($product->fresh()->is_upcoming);
    }

    public function test_archiving_hides_the_product_and_zeroes_its_stock(): void
    {
        $product = $this->product(['stock' => 3, 'is_upcoming' => true]);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertFalse($product->is_published);
        $this->assertFalse($product->is_upcoming);
        $this->assertSame(0, (int) $product->stock);
    }

    public function test_an_archived_product_can_be_restored_with_stock(): void
    {
        $product = $this->product(['is_published' => false, 'stock' => 0]);

        $this->actingAs($this->admin())
            ->patch(route('admin.products.restore', $product))
            ->assertRedirect();

        $product->refresh();

        $this->assertTrue($product->is_published);
        $this->assertSame(1, (int) $product->stock);
    }

    public function test_archived_products_cannot_be_marked_as_upcoming(): void
    {
        $product = $this->product(['is_published' => false]);

        $this->actingAs($this->admin())
            ->patch(route('admin.products.toggle-upcoming', $product))
            ->assertSessionHas('error');

        $this->assertFalse($product->fresh()->is_upcoming);
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
}
