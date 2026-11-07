<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_checkout_only_checks_out_the_selected_product(): void
    {
        $cartProduct = $this->publishedProduct([
            'title' => 'Utility Cargo Pants',
            'slug' => 'utility-cargo-pants',
            'sku' => 'NEON-0002',
        ]);
        $product = $this->publishedProduct();

        $this->withSession(['cart' => [$cartProduct->id => 1]]);

        $this->post(route('checkout.direct', $product))
            ->assertRedirect(route('checkout.create'));

        $this->assertSame($product->id, session('buy_now'));
        $this->assertSame([$cartProduct->id => 1], session('cart'));

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee($product->title)
            ->assertDontSee($cartProduct->title);
    }

    public function test_direct_checkout_rejects_a_product_that_is_not_available(): void
    {
        $product = $this->publishedProduct(['stock' => 0]);

        $this->from(route('products.show', $product))
            ->post(route('checkout.direct', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors('cart');
    }

    public function test_completing_a_direct_checkout_leaves_the_bag_untouched(): void
    {
        $cartProduct = $this->publishedProduct([
            'title' => 'Utility Cargo Pants',
            'slug' => 'utility-cargo-pants',
            'sku' => 'NEON-0002',
        ]);
        $product = $this->publishedProduct();

        $this->withSession([
            'cart' => [$cartProduct->id => 1],
            'buy_now' => $product->id,
        ]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'courier' => 'JNE',
        ])->assertRedirect();

        $this->assertSame([$cartProduct->id => 1], session('cart'));
        $this->assertNull(session('buy_now'));
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('order_items', ['product_id' => $cartProduct->id]);
    }

    public function test_opening_the_bag_discards_a_pending_direct_checkout(): void
    {
        $buyNowProduct = $this->publishedProduct();
        $cartProduct = $this->publishedProduct([
            'title' => 'Utility Cargo Pants',
            'slug' => 'utility-cargo-pants',
            'sku' => 'NEON-0002',
        ]);

        $this->withSession([
            'cart' => [$cartProduct->id => 1],
            'buy_now' => $buyNowProduct->id,
        ]);

        $this->get(route('cart.index'))->assertOk();

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee($cartProduct->title)
            ->assertDontSee($buyNowProduct->title);
    }

    public function test_product_page_offers_add_to_bag_and_direct_checkout(): void
    {
        $product = $this->publishedProduct();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('TAMBAH KE TAS')
            ->assertSee('BAYAR LANGSUNG')
            ->assertDontSee('DESKRIPSI & CERITA');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function publishedProduct(array $attributes = []): Product
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
