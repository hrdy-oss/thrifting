@extends('storefront.layout')

@section('title', 'Shopping Bag — NEON')

@section('content')
    <section class="page-shell narrow-shell"><div class="section-heading"><div><span class="eyebrow">NEON ARCHIVE / YOUR SELECTION</span><h1>SHOPPING BAG</h1></div><span class="eyebrow">{{ $products->count() }} PIECES</span></div>
        @forelse ($products as $product)<article class="cart-item" data-reveal><a href="{{ route('products.show', $product) }}" class="cart-photo"><img src="{{ $product->image_url ?: 'https://placehold.co/400x500/f3f3f3/111?text=NEON' }}" alt="{{ $product->title }}"></a><div class="cart-description"><span class="eyebrow">{{ strtoupper($product->category) }} · {{ $product->sku }}</span><h2><a href="{{ route('products.show', $product) }}">{{ $product->title }}</a></h2><p>Ukuran {{ $product->size ?: 'One size' }} · kondisi {{ $product->condition_score }}/10</p><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong></div><form method="POST" action="{{ route('cart.destroy', $product) }}">@csrf @method('DELETE')<button class="text-link cart-remove" type="submit">HAPUS</button></form></article>@empty<div class="empty-state"><h2>Keranjangmu masih kosong.</h2><a class="button button-dark" href="{{ route('products.index') }}">JELAJAHI KOLEKSI</a></div>@endforelse
        @if ($products->isNotEmpty())<aside class="summary-box" data-reveal><div><span>SUBTOTAL</span><strong>Rp{{ number_format($subtotal, 0, ',', '.') }}</strong></div><p>Ongkir dihitung setelah alamat dan layanan kurir dipilih.</p><a class="button button-dark button-full" href="{{ route('checkout.create') }}">LANJUT KE CHECKOUT →</a></aside>@endif
    </section>
@endsection
