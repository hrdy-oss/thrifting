<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101010">
    <title>@yield('title', 'Admin — NEON')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-page">
    <div class="admin-drawer" data-admin-drawer>
        <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <a class="admin-logo" href="{{ route('admin.dashboard') }}">NEON ARCHIVE<small>ADMIN / INVENTORY</small></a>
                <button class="admin-drawer-close" type="button" aria-label="Tutup menu" data-admin-drawer-close>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <nav>
                <a href="{{ route('admin.dashboard') }}">▦　DASHBOARD</a>
                <a href="{{ route('admin.products.index') }}">▣　INVENTORY & CATALOG</a>
                <a href="{{ route('admin.orders.index') }}">▤　SALES & ORDERS</a>
                <a href="{{ route('home') }}" target="_blank">↗　LIHAT TOKO</a>
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="admin-logout">LOG OUT</button></form>
        </aside>
    </div>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-menu-toggle" type="button" aria-label="Buka menu admin" data-admin-drawer-toggle>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="admin-topbar-title">@yield('title', 'Admin — NEON')</span>
            </div>
            <div class="admin-topbar-right">
                <span>NEON HQ — JAKARTA</span>
                <span>{{ auth()->user()->name }}</span>
            </div>
        </header>
        @if (session('success'))<div class="notice">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
        <div class="admin-content">@yield('content')</div>
    </main>
</body>
</html>
