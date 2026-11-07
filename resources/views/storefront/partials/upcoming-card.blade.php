<article class="product-card" data-reveal>
    <a href="{{ route('products.show', $product) }}" class="product-photo">
        <img src="{{ $product->image_url ?: 'https://placehold.co/800x1000/f3f3f3/111?text=NEON+UPCOMING' }}" alt="{{ $product->title }}" loading="lazy">
        <span class="edition-tag">SEGERA HADIR</span>
    </a>
    <div class="product-meta"><div><span class="eyebrow">{{ strtoupper($product->category) }} · UPCOMING</span><h3><a href="{{ route('products.show', $product) }}">{{ $product->title }}</a></h3><span class="muted">Ukuran {{ $product->size ?: 'One size' }}</span></div><strong>SEGERA</strong></div>
    <span class="upcoming-cta">HARGA SEGERA DIUMUMKAN</span>
</article>
