<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101010">
    <title>Admin Login — NEON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-brand">
            <div class="login-brand-content">
                <p class="login-welcome">Welcome to</p>
                <span class="login-logo" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M32 23v-3a6.5 6.5 0 1 1 6.5-6.5"/>
                        <path d="M32 23 7.8 41.2c-1.8 1.4-.9 4.2 1.4 4.2h45.6c2.3 0 3.2-2.8 1.4-4.2L32 23Z"/>
                    </svg>
                </span>
                <p class="login-brand-name">THRIFTPED</p>
                <p class="login-brand-copy">Panel kurasi internal untuk mengelola inventaris, pesanan, dan pengiriman THRIFTPED.</p>
            </div>
            <div class="login-brand-footer">
                <a href="{{ route('home') }}">KEMBALI KE TOKO</a>
                <span aria-hidden="true"></span>
                <a href="{{ route('products.index') }}">KATALOG</a>
            </div>
            <svg class="login-wave-side" viewBox="0 0 34 600" preserveAspectRatio="none" aria-hidden="true">
                <path fill="rgba(255, 255, 255, .18)" d="M34 0H18C4 50 30 100 18 150S4 250 18 300s16 100 0 150 -14 100 0 150h16Z"/>
                <path fill="#fff" d="M34 0H12C0 50 24 100 12 150S0 250 12 300s12 100 0 150 -12 100 0 150h22Z"/>
            </svg>
            <svg class="login-wave-mobile" viewBox="0 0 390 70" preserveAspectRatio="none" aria-hidden="true">
                <path fill="rgba(255, 255, 255, .22)" d="M0 34c58-26 118-26 195 0s137 30 195 2v34H0Z"/>
                <path fill="#fff" d="M0 50c58-24 118-24 195-2s137 26 195 6v16H0Z"/>
            </svg>
        </section>

        <section class="login-form-panel">
            <div class="login-form-inner">
                <span class="eyebrow">ADMIN / SECURE ACCESS</span>
                <h1 class="login-title">Masuk ke Dashboard</h1>
                <p class="login-sub">Gunakan akun admin untuk melanjutkan.</p>

                @if ($errors->any())
                    <p class="login-error" role="alert">{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="login-form">
                    @csrf
                    <label class="login-field">
                        <span>Email</span>
                        <span class="login-input-wrap">
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="username" inputmode="email" required autofocus>
                            <svg class="login-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </label>

                    <label class="login-field">
                        <span>Kata Sandi</span>
                        <span class="login-input-wrap">
                            <input type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                            <button class="login-input-icon login-password-toggle" type="button" aria-label="Tampilkan kata sandi" aria-pressed="false" data-password-toggle>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.8-6 10-6 10 6 10 6-3.8 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                            </button>
                        </span>
                    </label>

                    <div class="login-actions">
                        <button class="login-submit" type="submit">MASUK</button>
                        <a class="login-back" href="{{ route('home') }}">KEMBALI</a>
                    </div>

                    <p class="login-footnote">Akses terbatas untuk staf THRIFTPED.</p>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
