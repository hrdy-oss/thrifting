<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MidtransService
{
    /**
     * Whether Midtrans credentials are available.
     */
    public function isConfigured(): bool
    {
        return filled(config('midtrans.server_key'));
    }

    /**
     * Create a Snap transaction and store its token and payment URL on the order.
     */
    public function createSnapTransaction(Order $order): Order
    {
        $order->loadMissing('items');

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post(config('midtrans.snap_url'), $this->snapPayload($order));

        if ($response->failed()) {
            throw new RuntimeException(
                'Midtrans gagal membuat transaksi ('.$response->status().'): '.$response->body(),
            );
        }

        $data = $response->json();

        if (empty($data['token']) || empty($data['redirect_url'])) {
            throw new RuntimeException('Respons Midtrans tidak berisi token pembayaran.');
        }

        $order->forceFill([
            'payment_gateway' => 'midtrans',
            'payment_status' => 'pending',
            'payment_reference' => $data['token'],
            'payment_url' => $data['redirect_url'],
        ])->save();

        return $order->refresh();
    }

    /**
     * Verify and apply a Midtrans HTTP notification.
     */
    public function handleNotification(array $payload): Order
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $key) {
            if (! isset($payload[$key])) {
                throw new InvalidArgumentException("Payload Midtrans tidak lengkap: {$key}.");
            }
        }

        $expectedSignature = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey(),
        );

        if (! hash_equals($expectedSignature, (string) $payload['signature_key'])) {
            throw new InvalidArgumentException('Signature Midtrans tidak valid.');
        }

        $order = Order::query()->where('order_number', $payload['order_id'])->firstOrFail();

        if ((int) round((float) $payload['gross_amount']) !== (int) $order->total) {
            throw new InvalidArgumentException('Nominal pembayaran tidak sesuai dengan pesanan.');
        }

        $this->applyTransactionStatus($order, $payload);

        return $order->refresh();
    }

    /**
     * Pull the latest transaction status from Midtrans, useful when the
     * notification webhook cannot reach a local development server.
     */
    public function syncStatus(Order $order): Order
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        if (blank($order->payment_reference)) {
            return $order;
        }

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(20)
            ->get(config('midtrans.api_url').'/'.$order->order_number.'/status');

        if ($response->status() === 404 || ($response->json('status_code') === '404')) {
            return $order;
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Midtrans gagal memeriksa status ('.$response->status().'): '.$response->body(),
            );
        }

        $payload = $response->json();

        if (! empty($payload['gross_amount'])
            && (int) round((float) $payload['gross_amount']) !== (int) $order->total) {
            throw new RuntimeException('Nominal transaksi Midtrans tidak sesuai dengan pesanan.');
        }

        $this->applyTransactionStatus($order, $payload);

        return $order->refresh();
    }

    /**
     * Map a Midtrans transaction status onto the local order.
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyTransactionStatus(Order $order, array $payload): void
    {
        DB::transaction(function () use ($order, $payload): void {
            $lockedOrder = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            $transactionStatus = (string) ($payload['transaction_status'] ?? '');
            $fraudStatus = (string) ($payload['fraud_status'] ?? '');

            $isPaid = $transactionStatus === 'settlement'
                || ($transactionStatus === 'capture' && $fraudStatus === 'accept');
            $isChallenge = $transactionStatus === 'capture' && $fraudStatus === 'challenge';
            $shouldCancel = in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true);

            $paymentStatus = match (true) {
                $isPaid => 'paid',
                $isChallenge => 'challenge',
                $transactionStatus === 'pending' => 'pending',
                $transactionStatus === 'deny' => 'denied',
                $transactionStatus === 'expire' => 'expired',
                $transactionStatus === 'cancel' => 'cancelled',
                $transactionStatus === 'failure' => 'failed',
                $transactionStatus === 'refund' => 'refunded',
                $transactionStatus === 'partial_refund' => 'partially_refunded',
                $transactionStatus === 'chargeback' => 'chargeback',
                default => $transactionStatus !== '' ? $transactionStatus : $lockedOrder->payment_status,
            };

            $previousPaymentStatus = $lockedOrder->payment_status;

            $updates = [
                'payment_status' => $paymentStatus,
                'payment_method' => $payload['payment_type'] ?? $lockedOrder->payment_method,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? $lockedOrder->midtrans_transaction_id,
            ];

            if ($isPaid) {
                $updates['paid_at'] = $lockedOrder->paid_at ?? now();

                if (in_array($lockedOrder->status, ['awaiting_payment', 'processing'], true)) {
                    $updates['status'] = 'processing';
                }
            }

            if ($shouldCancel && ! in_array($lockedOrder->status, ['cancelled', 'shipped', 'delivered'], true)) {
                $updates['status'] = 'cancelled';

                foreach ($lockedOrder->items as $item) {
                    if ($item->product_id) {
                        $item->product()->increment('stock', $item->quantity);
                    }
                }
            }

            $failedStatuses = ['denied', 'expired', 'cancelled', 'failed'];

            $lockedOrder->update($updates);

            if ($previousPaymentStatus !== $paymentStatus) {
                OrderEvent::create([
                    'order_id' => $lockedOrder->id,
                    'status' => $updates['status'] ?? $lockedOrder->status,
                    'description' => $this->eventDescription($paymentStatus, $isPaid, $isChallenge, $failedStatuses),
                ]);
            }
        });
    }

    /**
     * Human readable event description for a payment status change.
     *
     * @param  array<int, string>  $failedStatuses
     */
    private function eventDescription(
        string $paymentStatus,
        bool $isPaid,
        bool $isChallenge,
        array $failedStatuses,
    ): string {
        if ($isPaid) {
            return 'Pembayaran diterima melalui Midtrans.';
        }

        if ($isChallenge) {
            return 'Pembayaran ditandai challenge oleh Midtrans dan menunggu peninjauan.';
        }

        if (in_array($paymentStatus, $failedStatuses, true)) {
            return 'Pembayaran tidak berhasil ('.$paymentStatus.'). Stok barang dikembalikan.';
        }

        return 'Status pembayaran Midtrans diperbarui: '.$paymentStatus.'.';
    }

    /**
     * Build the Snap transaction payload for an order.
     *
     * @return array<string, mixed>
     */
    private function snapPayload(Order $order): array
    {
        $nameParts = preg_split('/\s+/', trim($order->customer_name), 2);

        return [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total,
            ],
            'item_details' => $this->itemDetails($order),
            'customer_details' => [
                'first_name' => $nameParts[0] ?? $order->customer_name,
                'last_name' => $nameParts[1] ?? '',
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'shipping_address' => [
                    'address' => $order->shipping_address,
                    'city' => $order->city,
                    'postal_code' => $order->postal_code,
                    'country_code' => 'IDN',
                ],
            ],
            'callbacks' => [
                'finish' => route('orders.track', $order->tracking_token),
                'unfinish' => route('orders.track', $order->tracking_token),
                'error' => route('orders.track', $order->tracking_token),
            ],
            'expiry' => [
                'unit' => 'hours',
                'duration' => 24,
            ],
        ];
    }

    /**
     * Build item details whose total matches the order total.
     *
     * @return array<int, array{id: string, price: int, quantity: int, name: string}>
     */
    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item): array => [
            'id' => Str::limit((string) ($item->product_sku ?: $item->product_id ?: $item->id), 50, ''),
            'price' => (int) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'name' => Str::limit($item->product_title, 50, ''),
        ])->values()->all();

        if ((int) $order->shipping_cost > 0) {
            $items[] = [
                'id' => 'shipping',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => Str::limit('Ongkos Kirim '.$order->courier, 50, ''),
            ];
        }

        if ((int) $order->discount > 0) {
            $items[] = [
                'id' => 'discount',
                'price' => -1 * (int) $order->discount,
                'quantity' => 1,
                'name' => 'Diskon',
            ];
        }

        $itemTotal = collect($items)->sum(fn (array $item): int => $item['price'] * $item['quantity']);

        if ($itemTotal !== (int) $order->total) {
            return [[
                'id' => Str::limit($order->order_number, 50, ''),
                'price' => (int) $order->total,
                'quantity' => 1,
                'name' => Str::limit('Pesanan '.$order->order_number, 50, ''),
            ]];
        }

        return $items;
    }

    /**
     * Resolve the configured server key or fail loudly.
     */
    private function serverKey(): string
    {
        $serverKey = trim((string) config('midtrans.server_key'));

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi di file .env.');
        }

        $isProductionKey = str_starts_with($serverKey, 'Mid-server-');
        $isSandboxKey = str_starts_with($serverKey, 'SB-Mid-server-');

        if (! config('midtrans.is_production') && ! $isSandboxKey) {
            throw new RuntimeException(
                'MIDTRANS_SERVER_KEY yang dipakai bukan kunci sandbox. '
                .'Kunci sandbox diawali "SB-Mid-server-". '
                .'Ambil dari Dashboard Midtrans saat environment Sandbox dipilih, '
                .'atau set MIDTRANS_IS_PRODUCTION=true jika memang ingin memakai kunci production.',
            );
        }

        if (config('midtrans.is_production') && ! $isProductionKey) {
            throw new RuntimeException(
                'MIDTRANS_SERVER_KEY yang dipakai bukan kunci production. '
                .'Kunci production diawali "Mid-server-". '
                .'Set MIDTRANS_IS_PRODUCTION=false untuk memakai sandbox.',
            );
        }

        return $serverKey;
    }
}
