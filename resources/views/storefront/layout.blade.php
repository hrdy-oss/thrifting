<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>THRIFTPED</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront">
    <header class="site-header">
        <a class="wordmark" href="{{ route('home') }}">THRIFTPED</a>
        <nav class="main-nav"><a href="{{ route('home') }}">Beranda</a><a href="{{ route('products.index') }}">Shop</a><a href="{{ route('upcoming.index') }}">Upcoming</a><a href="#about">Tentang</a></nav>
        <div class="header-actions"><form action="{{ route('products.index') }}" method="GET" class="search-mini"><input name="q" placeholder="Cari barang" value="{{ request('q') }}"><button aria-label="Cari">⌕</button></form><a href="{{ route('cart.index') }}" class="bag-link">Tas <span>{{ count(session('cart', [])) }}</span></a></div>
    </header>
    @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="notice notice-error">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
    <main>@yield('content')</main>
    <footer class="site-footer" id="about"><div><a class="wordmark" href="{{ route('home') }}">THRIFTPED</a><p>Streetwear terkurasi. Setiap barang punya cerita.</p></div><div><strong>SHOP</strong><a href="{{ route('products.index') }}">Semua produk</a><a href="{{ route('upcoming.index') }}">Upcoming</a><a href="{{ route('cart.index') }}">Keranjang</a></div><div><strong>BANTUAN</strong><a href="{{ route('products.index') }}">Kebijakan pengiriman</a><a href="{{ route('products.index') }}">Pusat bantuan</a></div><small>© {{ now()->year }} THRIFTPED ARCHIVE</small></footer>
</body>
</html>
