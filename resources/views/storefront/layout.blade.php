<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101010">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'THRIFTPED')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront">
    <div class="announcement">PENGIRIMAN GRATIS UNTUK PESANAN DI ATAS Rp. 500.000</div>
    <header class="site-header">
        <a class="wordmark" href="{{ route('home') }}">THRIFTPED</a>
        <nav class="main-nav" id="main-menu">
            <a href="{{ route('home') }}">Beranda</a>
            <a href="{{ route('products.index') }}">Shop</a>
            <a href="{{ route('products.index', ['category' => 'hoodies']) }}">Koleksi</a>
            <a href="#about">Tentang</a>
        </nav>
        <div class="header-actions">
            <button class="search-trigger" type="button" aria-label="Cari produk" data-search-overlay-toggle>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
            </button>
            <a href="{{ route('cart.index') }}" class="bag-link" aria-label="Keranjang, {{ count(session('cart', [])) }} barang">
                <svg class="bag-icon" viewBox="0 0 32 32" aria-hidden="true"><path d="M3.5 4.5h3.7l4 17.2c.25 1.05 1.15 1.8 2.25 1.8h12.2" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.8 8.2h19.1l-4.1 11.5H12.5L9.8 8.2Z" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M15.2 11.1v5.3m7.1-5.3v5.3" stroke="white" stroke-width="1.5" stroke-linecap="round"/><circle cx="14.1" cy="27" r="2.5" fill="currentColor"/><circle cx="24.1" cy="27" r="2.5" fill="currentColor"/></svg>
                <span class="bag-label">Keranjang</span>
                <span class="bag-count">{{ count(session('cart', [])) }}</span>
            </a>
        </div>
    </header>

    <div class="search-overlay" data-search-overlay>
        <div class="search-overlay-backdrop" data-search-overlay-close></div>
        <div class="search-overlay-panel">
            <form action="{{ route('products.index') }}" method="GET" class="search-overlay-form">
                <svg class="search-overlay-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                <input name="q" placeholder="Cari item, era, atau ukuran" value="{{ request('q') }}" autocomplete="off">
                <button class="search-overlay-close" type="button" aria-label="Tutup pencarian" data-search-overlay-close>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </form>
        </div>
    </div>

    <div class="site-drawer" id="site-drawer" data-site-drawer>
        <div class="site-drawer-backdrop" data-site-drawer-close></div>
        <aside class="site-drawer-panel">
            <div class="site-drawer-header">
                <span class="wordmark">THRIFTPED</span>
                <button class="site-drawer-close" type="button" aria-label="Tutup menu" data-site-drawer-close>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <nav class="site-drawer-nav">
                <a href="{{ route('home') }}">Beranda</a>
                <a href="{{ route('products.index') }}">Semua Produk</a>
                <a href="{{ route('products.index', ['category' => 'hoodies']) }}">Hoodies</a>
                <a href="{{ route('products.index', ['category' => 't-shirts']) }}">T-Shirts</a>
                <a href="{{ route('products.index', ['category' => 'pants']) }}">Pants</a>
                <a href="{{ route('products.index', ['category' => 'jackets']) }}">Jackets</a>
                <a href="#about">Tentang</a>
                <a href="{{ route('admin.login') }}">Admin</a>
            </nav>
        </aside>
    </div>

    @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="notice notice-error">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
    <main class="site-main">@yield('content')</main>
    <footer class="site-footer" id="about"><div><a class="wordmark" href="{{ route('home') }}">THRIFTPED</a><p>Streetwear terkurasi. Setiap barang punya cerita.</p></div><div><strong>SHOP</strong><a href="{{ route('products.index') }}">Semua produk</a><a href="{{ route('cart.index') }}">Keranjang</a></div><div><strong>BANTUAN</strong><a href="{{ route('products.index') }}">Kebijakan pengiriman</a><a href="{{ route('admin.login') }}">Admin</a></div><small>© {{ now()->year }} THRIFTPED ARCHIVE</small></footer>
    @include('storefront.partials.mobile-bottom-nav')
</body>
</html>
