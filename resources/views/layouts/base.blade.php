<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FLEX — Flexible Learning Experience')</title>
    <meta name="theme-color" content="#1E1B4B">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script>
        // tema: localStorage.tema = terang | gelap | (kosong = ikut sistem). Dipasang sebelum CSS supaya tidak berkedip.
        (function () {
            var d = document.documentElement, m = window.matchMedia('(prefers-color-scheme: light)');
            function pilihan() { try { return localStorage.getItem('tema') || 'otomatis'; } catch (e) { return 'otomatis'; } }
            function terapkan() {
                var p = pilihan(), t = p === 'terang' ? 'light' : p === 'gelap' ? 'dark' : (m.matches ? 'light' : 'dark');
                d.dataset.theme = t;
                var meta = document.querySelector('meta[name=theme-color]');
                if (meta) meta.content = t === 'light' ? '#F6F4FF' : '#1E1B4B';
                // iOS: tema terang pakai status bar biasa (teks gelap), tema gelap menyatu dengan header
                var sb = document.querySelector('meta[name=apple-mobile-web-app-status-bar-style]');
                if (sb) sb.content = t === 'light' ? 'default' : 'black-translucent';
                document.querySelectorAll('[data-tema-pilih]').forEach(function (b) { b.classList.toggle('on', b.dataset.temaPilih === p); b.setAttribute('aria-pressed', b.dataset.temaPilih === p); });
            }
            window.FlexTema = {
                set: function (p) { try { p === 'otomatis' ? localStorage.removeItem('tema') : localStorage.setItem('tema', p); } catch (e) {} terapkan(); }
            };
            if (m.addEventListener) m.addEventListener('change', terapkan);
            terapkan();
            document.addEventListener('DOMContentLoaded', terapkan);
            document.addEventListener('click', function (e) {
                var b = e.target.closest('[data-tema-toggle],[data-tema-pilih]');
                if (!b) return;
                FlexTema.set(b.dataset.temaPilih || (d.dataset.theme === 'dark' ? 'terang' : 'gelap'));
            });
        })();
    </script>
    <link rel="preload" href="/fonts/dm-sans.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/fredoka.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="FLEX">
    <link rel="stylesheet" href="/css/app.css?v=23">
    @stack('head')
</head>
<body class="@yield('body-class')">
@yield('body')
<script>
    window.CSRF = document.querySelector('meta[name=csrf-token]').content;
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
<script src="/js/ui.js?v=23"></script>
@stack('scripts')
</body>
</html>
