@extends('storefront.layout')

@section('title', 'Upcoming — NEON')

@section('content')
    <section class="page-shell">
        <div class="section-heading" data-reveal><div><span class="eyebrow">NEXT DROP / COMING SOON</span><h1>UPCOMING PIECES</h1></div><span class="eyebrow">{{ $products->total() }} ITEMS</span></div>
        <p class="upcoming-lead">Barang yang sedang disiapkan untuk drop berikutnya. Harga dan tanggal rilis akan diumumkan di sini.</p>
        <div class="product-grid" data-reveal-stagger>@forelse ($products as $product)@include('storefront.partials.upcoming-card', ['product' => $product])@empty<p>Belum ada barang upcoming. Cek lagi nanti.</p>@endforelse</div>
        <div class="pagination">{{ $products->links() }}</div>
    </section>
@endsection
