<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'midtrans.server_key' => 'SB-Mid-server-testing',
            'midtrans.client_key' => 'SB-Mid-client-testing',
            'midtrans.is_production' => false,
            'midtrans.snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
            'midtrans.api_url' => 'https://api.sandbox.midtrans.com/v2',
        ]);
    }

    public function test_checkout_creates_a_snap_transaction_and_redirects_to_midtrans(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*app.sandbox.midtrans.com/snap/v1/transactions*' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ], 201),
        ]);

        $product = Product::create([
            'title' => 'Court Runner Low',
            'slug' => 'court-runner-low',
            'sku' => 'NEON-0001',
            'description' => 'White leather low-top in good condition.',
            'category' => 'shoes',
            'price' => 1299000,
            'stock' => 1,
            'is_published' => true,
        ]);

        $this->withSession(['buy_now' => $product->id]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'courier' => 'JNE',
        ])->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'budi@example.com',
            'payment_gateway' => 'midtrans',
            'payment_status' => 'pending',
            'payment_reference' => 'snap-token-123',
            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
        ]);
    }

    public function test_a_settled_notification_marks_the_order_as_paid_and_processing(): void
    {
        Http::preventStrayRequests();

        $order = $this->order();

        $this->postJson(route('midtrans.notification'), $this->notification($order, [
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
        ]))->assertOk()->assertJsonPath('payment_status', 'paid');

        $order->refresh();

        $this->assertSame('processing', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('qris', $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'status' => 'processing',
            'description' => 'Pembayaran diterima melalui Midtrans.',
        ]);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        Http::preventStrayRequests();

        $order = $this->order();
        $payload = $this->notification($order);
        $payload['signature_key'] = 'not-a-valid-signature';

        $this->postJson(route('midtrans.notification'), $payload)->assertForbidden();

        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_an_expired_notification_cancels_the_order_and_restores_stock_only_once(): void
    {
        Http::preventStrayRequests();

        $product = Product::create([
            'title' => 'Court Runner Low',
            'slug' => 'court-runner-low',
            'sku' => 'NEON-0001',
            'description' => 'White leather low-top in good condition.',
            'category' => 'shoes',
            'price' => 1299000,
            'stock' => 0,
            'is_published' => true,
        ]);

        $order = $this->order(total: 1299000);
        $order->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'product_sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 1,
        ]);

        $payload = $this->notification($order, ['transaction_status' => 'expire']);

        $this->postJson(route('midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('midtrans.notification'), $payload)->assertOk();

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame('expired', $order->payment_status);
        $this->assertSame(1, $product->refresh()->stock);
    }

    public function test_the_payment_status_can_be_synced_from_the_api(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            '*api.sandbox.midtrans.com/v2/*/status*' => Http::response([
                'status_code' => '200',
                'order_id' => 'NEON-0001',
                'gross_amount' => '1299000.00',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'payment_type' => 'gopay',
                'transaction_id' => 'tx-sync-123',
            ]),
        ]);

        $order = $this->order(paymentReference: 'snap-token-123', paymentStatus: 'pending');

        $this->post(route('orders.pay.sync', $order->tracking_token))
            ->assertRedirect(route('orders.track', $order->tracking_token));

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('gopay', $order->payment_method);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function notification(Order $order, array $overrides = []): array
    {
        $statusCode = '200';
        $grossAmount = number_format((float) $order->total, 2, '.', '');

        return [
            'order_id' => $order->order_number,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => hash('sha512', $order->order_number.$statusCode.$grossAmount.config('midtrans.server_key')),
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'tx-'.Str::random(8),
            ...$overrides,
        ];
    }

    private function order(
        int $total = 1299000,
        string $paymentReference = '',
        string $paymentStatus = 'unpaid',
    ): Order {
        return Order::create([
            'order_number' => 'NEON-'.Str::upper(Str::random(8)),
            'tracking_token' => (string) Str::uuid(),
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'shipping_address' => 'Jl. Merdeka No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'courier' => 'JNE',
            'subtotal' => $total,
            'shipping_cost' => 0,
            'total' => $total,
            'status' => 'awaiting_payment',
            'payment_status' => $paymentStatus,
            'payment_reference' => $paymentReference,
        ]);
    }
}
