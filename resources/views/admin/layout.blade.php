<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111111">
    <title>@yield('title', 'Admin — NEON')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">▦　DASHBOARD</a>
                <a class="{{ request()->routeIs('admin.products.index', 'admin.products.create', 'admin.products.edit') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">▣　INVENTORY & CATALOG</a>
                <a class="{{ request()->routeIs('admin.products.upcoming') ? 'is-active' : '' }}" href="{{ route('admin.products.upcoming') }}">◈　UPCOMING</a>
                <a class="{{ request()->routeIs('admin.products.archived') ? 'is-active' : '' }}" href="{{ route('admin.products.archived') }}">▤　ARSIP</a>
                <a class="{{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" href="{{ route('admin.orders.index') }}">▥　SALES & ORDERS</a>
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
        @if (session('error'))<div class="notice notice-error">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="notice notice-error">{{ $errors->first() }}</div>@endif
        <div class="admin-content">@yield('content')</div>
    </main>
    <div class="camera-modal" data-camera-modal hidden>
        <div class="camera-modal-backdrop" data-camera-close></div>
        <div class="camera-modal-panel" role="dialog" aria-modal="true" aria-label="Ambil foto dari kamera">
            <div class="camera-modal-head">
                <strong>AMBIL FOTO</strong>
                <button class="camera-modal-close" type="button" aria-label="Tutup kamera" data-camera-close>×</button>
            </div>
            <div class="camera-modal-stage">
                <video data-camera-video playsinline autoplay muted></video>
            </div>
            <p class="camera-modal-status" data-camera-status>Izinkan akses kamera untuk mulai memotret.</p>
            <div class="camera-modal-actions">
                <button class="button button-dark" type="button" data-camera-capture disabled>AMBIL FOTO</button>
                <button class="button button-outline" type="button" data-camera-close>BATAL</button>
            </div>
        </div>
    </div>
</body>
</html>
