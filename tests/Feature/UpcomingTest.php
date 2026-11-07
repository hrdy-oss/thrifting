<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingTest extends TestCase
{
    use RefreshDatabase;

    public function test_upcoming_page_lists_only_upcoming_products_without_prices(): void
    {
        $upcomingProduct = $this->product([
            'title' => 'Archive Varsity Jacket',
            'slug' => 'archive-varsity-jacket',
            'sku' => 'NEON-UP-01',
            'price' => 1899000,
            'is_upcoming' => true,
        ]);
        $liveProduct = $this->product();

        $this->get(route('upcoming.index'))
            ->assertOk()
            ->assertSee($upcomingProduct->title)
            ->assertSee('SEGERA HADIR')
            ->assertDontSee($liveProduct->title)
            ->assertDontSee('Rp1.899.000');
    }

    public function test_shop_and_home_exclude_upcoming_products(): void
    {
        $upcomingProduct = $this->product([
            'title' => 'Archive Varsity Jacket',
            'slug' => 'archive-varsity-jacket',
            'sku' => 'NEON-UP-01',
            'is_upcoming' => true,
        ]);
        $liveProduct = $this->product();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee($liveProduct->title)
            ->assertDontSee($upcomingProduct->title);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($liveProduct->title)
            ->assertDontSee($upcomingProduct->title);
    }

    public function test_upcoming_product_detail_is_viewable_but_not_purchasable(): void
    {
        $product = $this->product([
            'title' => 'Archive Varsity Jacket',
            'slug' => 'archive-varsity-jacket',
            'sku' => 'NEON-UP-01',
            'price' => 1899000,
            'is_upcoming' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('HARGA SEGERA')
            ->assertSee('SEGERA HADIR')
            ->assertDontSee('TAMBAH KE TAS')
            ->assertDontSee('BAYAR LANGSUNG')
            ->assertDontSee('Rp1.899.000');
    }

    public function test_upcoming_products_cannot_be_added_to_the_bag_or_bought_directly(): void
    {
        $product = $this->product([
            'title' => 'Archive Varsity Jacket',
            'slug' => 'archive-varsity-jacket',
            'sku' => 'NEON-UP-01',
            'is_upcoming' => true,
        ]);

        $this->from(route('products.show', $product))
            ->post(route('cart.store', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('cart');

        $this->assertEmpty(session('cart', []));

        $this->from(route('products.show', $product))
            ->post(route('checkout.direct', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('cart');

        $this->assertNull(session('buy_now'));
    }

    public function test_related_products_exclude_upcoming_pieces(): void
    {
        $liveProduct = $this->product();
        $upcomingProduct = $this->product([
            'title' => 'Archive Varsity Jacket',
            'slug' => 'archive-varsity-jacket',
            'sku' => 'NEON-UP-01',
            'is_upcoming' => true,
        ]);

        $this->get(route('products.show', $liveProduct))
            ->assertOk()
            ->assertDontSee($upcomingProduct->title);
    }

    public function test_admin_can_mark_a_product_as_upcoming(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'title' => $product->title,
                'sku' => $product->sku,
                'category' => $product->category,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_upcoming' => '1',
            ])->assertRedirect(route('admin.products.index'));

        $this->assertTrue($product->fresh()->is_upcoming);
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
