@extends('storefront.layout')

@section('title', $product->title.' — NEON')

@section('content')
    <section class="page-shell"><div class="product-detail"><div class="product-detail-photo" data-reveal><img src="{{ $product->image_url ?: 'https://placehold.co/900x1100/f3f3f3/111?text=NEON+ARCHIVE' }}" alt="{{ $product->title }}"><span class="edition-tag">1-OF-1 ARCHIVE PIECE</span></div><div class="product-detail-info" data-reveal><span class="eyebrow">{{ strtoupper($product->category) }} / {{ $product->era ?: 'CURATED DROP' }}</span><h1>{{ strtoupper($product->title) }}</h1><div class="price-line"><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong>@if ($product->compare_at_price)<del>Rp{{ number_format($product->compare_at_price, 0, ',', '.') }}</del>@endif</div><p class="availability">● Tersedia satu barang · Ukuran {{ $product->size ?: 'One size' }}</p><div class="grade-box"><strong>GRADE {{ $product->condition_score ?? '—' }} / 10</strong><p>{{ $product->condition_notes ?: 'Barang thrift pilihan, kondisi sudah diperiksa.' }}</p></div><div class="spec-grid"><span>ERA<strong>{{ $product->era ?: 'Vintage' }}</strong></span><span>TAG SIZE<strong>{{ $product->size ?: 'One size' }}</strong></span>@foreach ($product->measurements ?? [] as $label => $value)<span>{{ strtoupper(str_replace('_', ' ', $label)) }}<strong>{{ $value }}</strong></span>@endforeach</div><p>{{ $product->description }}</p><form method="POST" action="{{ route('cart.store', $product) }}" class="product-detail-form"> @csrf<button class="button button-dark button-full" type="submit">TAMBAH KE TAS — Rp{{ number_format($product->price, 0, ',', '.') }}</button></form><div class="detail-accordion"><details open><summary>DESKRIPSI & CERITA</summary><p>{{ $product->description }}</p></details><details><summary>PENGIRIMAN</summary><p>Opsi pengiriman dan estimasi biaya tersedia saat checkout.</p></details><details><summary>KONDISI THRIFT</summary><p>Foto, ukuran, serta catatan kondisi produk menjadi referensi sebelum membeli.</p></details></div></div></div></section>
    @if ($relatedProducts->isNotEmpty())<section class="section-block section-muted"><div class="section-heading"><div><span class="eyebrow">CURATED DROP</span><h2>MORE ONE-OF-1 PIECES</h2></div></div><div class="product-grid" data-reveal-stagger>@foreach ($relatedProducts as $relatedProduct)@include('storefront.partials.product-card', ['product' => $relatedProduct])@endforeach</div></section>@endif
    <div class="product-sticky-bar">
        <div class="product-sticky-bar-inner">
            <div class="product-sticky-info">
                <span class="product-sticky-title">{{ $product->title }}</span>
                <strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong>
            </div>
            <form method="POST" action="{{ route('cart.store', $product) }}">
                @csrf
                <button class="button button-dark" type="submit">TAMBAH KE TAS</button>
            </form>
        </div>
    </div>
@endsection
