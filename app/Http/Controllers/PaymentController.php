<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Send the customer to the Midtrans Snap payment page.
     */
    public function pay(string $token, MidtransService $midtrans): RedirectResponse
    {
        $order = Order::query()->where('tracking_token', $token)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.track', $token)
                ->with('success', 'Pesanan ini sudah dibayar.');
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.track', $token)
                ->with('error', 'Pesanan ini sudah dibatalkan sehingga tidak bisa dibayar.');
        }

        if (! $midtrans->isConfigured()) {
            return redirect()->route('orders.track', $token)
                ->with('error', 'Pembayaran Midtrans belum dikonfigurasi. Hubungi admin toko.');
        }

        try {
            if ($order->payment_status === 'pending' && filled($order->payment_url)) {
                return redirect()->away($order->payment_url);
            }

            $order = $midtrans->createSnapTransaction($order);

            return redirect()->away($order->payment_url);
        } catch (Throwable $exception) {
            report($exception);

            $message = config('app.debug')
                ? 'Gagal membuat halaman pembayaran: '.$exception->getMessage()
                : 'Gagal membuat halaman pembayaran. Coba lagi beberapa saat lagi.';

            return redirect()->route('orders.track', $token)
                ->with('error', $message);
        }
    }

    /**
     * Re-check the payment status with Midtrans.
     */
    public function sync(string $token, MidtransService $midtrans): RedirectResponse
    {
        $order = Order::query()->where('tracking_token', $token)->firstOrFail();

        if (! $midtrans->isConfigured()) {
            return redirect()->route('orders.track', $token)
                ->with('error', 'Pembayaran Midtrans belum dikonfigurasi.');
        }

        try {
            $order = $midtrans->syncStatus($order);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('orders.track', $token)
                ->with('error', 'Gagal memeriksa status pembayaran ke Midtrans.');
        }

        $message = match ($order->payment_status) {
            'paid' => 'Pembayaran diterima. Pesanan akan segera diproses.',
            'pending' => 'Pembayaran masih menunggu. Selesaikan pembayaran kamu.',
            'challenge' => 'Pembayaran sedang ditinjau oleh Midtrans.',
            'expired' => 'Pembayaran sudah kedaluwarsa. Silakan buat pembayaran baru.',
            'denied' => 'Pembayaran ditolak. Silakan coba metode pembayaran lain.',
            default => 'Status pembayaran diperbarui: '.$order->payment_status.'.',
        };

        return redirect()->route('orders.track', $token)->with('success', $message);
    }
}
