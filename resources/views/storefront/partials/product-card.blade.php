<article class="product-card" data-reveal>
    <a href="{{ route('products.show', $product) }}" class="product-photo">
        <img src="{{ $product->image_url ?: 'https://placehold.co/800x1000/f3f3f3/111?text=NEON+ARCHIVE' }}" alt="{{ $product->title }}" loading="lazy">
        <span class="edition-tag">1-OF-1</span>
    </a>
    <div class="product-meta"><div><span class="eyebrow">{{ strtoupper($product->category) }} · GRADE {{ $product->condition_score ?? '—' }}</span><h3><a href="{{ route('products.show', $product) }}">{{ $product->title }}</a></h3><span class="muted">Ukuran {{ $product->size ?: 'One size' }}</span></div><strong>Rp{{ number_format($product->price, 0, ',', '.') }}</strong></div>
    <form method="POST" action="{{ route('cart.store', $product) }}">@csrf<button class="button button-dark button-full" type="submit">TAMBAH KE TAS</button></form>
</article>
