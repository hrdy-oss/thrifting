@php
    $cartCount = count(session('cart', []));
    $current = request()->route()?->getName();
@endphp
<nav class="mobile-bottom-nav" aria-label="Navigasi bawah">
    <a class="mobile-bottom-nav-item {{ $current === 'home' ? 'is-active' : '' }}" href="{{ route('home') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
        <span>Beranda</span>
    </a>
    <a class="mobile-bottom-nav-item {{ $current === 'products.index' ? 'is-active' : '' }}" href="{{ route('products.index') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span>Shop</span>
    </a>
    <a class="mobile-bottom-nav-item {{ $current === 'cart.index' ? 'is-active' : '' }}" href="{{ route('cart.index') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6 5 3H2"/></svg>
        <span>Keranjang</span>
        @if ($cartCount > 0)
            <span class="mobile-bottom-nav-badge">{{ $cartCount }}</span>
        @endif
    </a>
    <button class="mobile-bottom-nav-item" type="button" data-site-drawer-toggle>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>Menu</span>
    </button>
</nav>
