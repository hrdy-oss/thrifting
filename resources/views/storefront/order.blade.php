@extends('storefront.layout')

@section('title', 'Order '.$order->order_number.' — NEON')

@section('content')
    <section class="page-shell narrow-shell"><span class="eyebrow">ORDER CONFIRMATION / {{ $order->order_number }}</span><h1>PESANAN TERCATAT.</h1>@if ($order->payment_status === 'paid')
        <div class="payment-placeholder"><strong>PEMBAYARAN DITERIMA</strong><p>Terima kasih. Pembayaran sudah dikonfirmasi melalui Midtrans @if ($order->payment_method) ({{ strtoupper(str_replace('_', ' ', $order->payment_method)) }})@endif. Pesanan akan segera diproses.</p></div>
    @elseif ($order->status === 'cancelled')
        <div class="payment-placeholder"><strong>PESANAN DIBATALKAN</strong><p>Pembayaran tidak berhasil atau pesanan dibatalkan. Hubungi admin jika kamu membutuhkan bantuan.</p></div>
    @else
        <div class="payment-placeholder">
            <strong>MENUNGGU PEMBAYARAN</strong>
            <p>Status pembayaran: {{ strtoupper(str_replace('_', ' ', $order->payment_status ?? 'unpaid')) }}. Selesaikan pembayaran melalui Midtrans untuk memproses pesanan.</p>
            <div class="payment-actions">
                <form method="POST" action="{{ route('orders.pay', $order->tracking_token) }}">@csrf<button class="button button-dark" type="submit">BAYAR SEKARANG</button></form>
                <form method="POST" action="{{ route('orders.pay.sync', $order->tracking_token) }}">@csrf<button class="button button-outline" type="submit">CEK STATUS PEMBAYARAN</button></form>
            </div>
            @unless (config('midtrans.is_production'))
                <p class="muted">Sandbox Midtrans aktif — pembayaran hanya untuk pengujian.</p>
            @endunless
        </div>
    @endif<div class="order-summary" data-reveal><h2>RINGKASAN PESANAN</h2>@foreach ($order->items as $item)<div class="order-line"><span>{{ $item->product_title }} × {{ $item->quantity }}</span><strong>Rp{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}</strong></div>@endforeach<div class="order-line"><span>Kurir {{ $order->courier }}</span><strong>Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</strong></div><div class="order-line order-total"><span>TOTAL</span><strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong></div></div><h2>PERJALANAN PESANAN</h2><div class="timeline" data-reveal>@foreach ($order->events as $event)<div class="timeline-entry"><span class="timeline-dot"></span><div><strong>{{ strtoupper(str_replace('_', ' ', $event->status)) }}</strong><p>{{ $event->description }}</p><small>{{ $event->created_at->format('d M Y, H:i') }}</small></div></div>@endforeach</div><p class="muted">Tautan pelacakan ini bersifat pribadi. Simpan untuk memeriksa status pesanan kapan saja.</p></section>
@endsection
