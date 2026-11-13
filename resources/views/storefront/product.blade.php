@extends('storefront.layout')

@section('title', $product->title.' — NEON')

@section('content')
    @php
        $galleryItems = $product->galleryItems();
        $primaryPhoto = $product->image_url ?: ($galleryItems[0]['url'] ?? 'https://placehold.co/900x1100/f3f3f3/111?text=NEON+ARCHIVE');
    @endphp
    <section class="page-shell"><div class="product-detail"><div class="product-detail-photo" data-reveal><div class="product-detail-main" data-product-gallery><img src="{{ $primaryPhoto }}" alt="{{ $product->title }}" data-product-gallery-main><span class="edition-tag">{{ $product->is_upcoming ? 'SEGERA HADIR' : '1-OF-1 ARCHIVE PIECE' }}</span>@if ($galleryItems !== [])<div class="product-gallery-thumbs" data-product-gallery-thumbs>@foreach ($galleryItems as $index => $item)<button type="button" class="product-gallery-thumb {{ $item['url'] === $primaryPhoto ? 'is-active' : '' }}" data-product-gallery-thumb data-src="{{ $item['url'] }}" aria-label="Lihat foto {{ $index + 1 }}"><img src="{{ $item['url'] }}" alt="{{ $product->title }} foto {{ $index + 1 }}" loading="lazy"></button>@endforeach</div>@endif</div></div><div class="product-detail-info" data-reveal><span class="eyebrow">{{ strtoupper($product->category) }} / {{ $product->era ?: 'CURATED DROP' }}</span><h1>{{ strtoupper($product->title) }}</h1><div class="price-line">@if ($product->is_upcoming)<strong>HARGA SEGERA</strong>@else<strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong>@if ($product->compare_at_price)<del>Rp{{ number_format($product->compare_at_price, 0, ',', '.') }}</del>@endif
                    @endif
                </div><p class="availability">@if ($product->is_upcoming)● Segera hadir · belum bisa dibeli @else● Tersedia satu barang · Ukuran {{ $product->size ?: 'One size' }}@endif</p><div class="grade-box"><strong>GRADE {{ $product->condition_score ?? '—' }} / 10</strong><p>{{ $product->condition_notes ?: 'Barang thrift pilihan, kondisi sudah diperiksa.' }}</p></div><div class="spec-grid"><span>ERA<strong>{{ $product->era ?: 'Vintage' }}</strong></span><span>TAG SIZE<strong>{{ $product->size ?: 'One size' }}</strong></span>@foreach ($product->measurements ?? [] as $label => $value)<span>{{ strtoupper(str_replace('_', ' ', $label)) }}<strong>{{ $value }}</strong></span>@endforeach</div><p>{{ $product->description }}</p>@if ($product->is_upcoming)<div class="product-actions"><span class="upcoming-notice">SEGERA HADIR — BARANG INI BELUM TERSEDIA UNTUK DIBELI</span></div>@else<div class="product-actions"><form method="POST" action="{{ route('cart.store', $product) }}" class="product-detail-form"> @csrf<button class="button button-dark button-full" type="submit">TAMBAH KE TAS — Rp{{ number_format($product->price, 0, ',', '.') }}</button></form><form method="POST" action="{{ route('checkout.direct', $product) }}" class="product-detail-form"> @csrf<button class="button button-outline button-full" type="submit">BAYAR LANGSUNG</button></form></div>@endif</div></div></section>
    @if ($relatedProducts->isNotEmpty())<section class="section-block section-muted"><div class="section-heading"><div><span class="eyebrow">CURATED DROP</span><h2>MORE ONE-OF-1 PIECES</h2></div></div><div class="product-grid" data-reveal-stagger>@foreach ($relatedProducts as $relatedProduct)@include('storefront.partials.product-card', ['product' => $relatedProduct])@endforeach</div></section>@endif
    @if (! $product->is_upcoming)
        <div class="product-sticky-bar">
            <div class="product-sticky-bar-inner">
                <div class="product-sticky-info">
                    <span class="product-sticky-title">{{ $product->title }}</span>
                    <strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong>
                </div>
                <div class="product-sticky-actions">
                    <form method="POST" action="{{ route('cart.store', $product) }}">
                        @csrf
                        <button class="button button-dark" type="submit">TAMBAH KE TAS</button>
                    </form>
                    <form method="POST" action="{{ route('checkout.direct', $product) }}">
                        @csrf
                        <button class="button button-light" type="submit">BAYAR LANGSUNG</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
