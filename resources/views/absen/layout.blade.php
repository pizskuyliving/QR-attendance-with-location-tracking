<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Absen QR')</title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --line: #334155;
            --text: #f1f5f9; --muted: #94a3b8;
            --ok: #22c55e; --warn: #f59e0b; --err: #ef4444; --accent: #f97316;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100dvh; background: var(--bg); color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: env(safe-area-inset-top) 16px env(safe-area-inset-bottom);
        }
        .wrap { max-width: 480px; margin: 0 auto; padding: 20px 0 32px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px; }
        .muted { color: var(--muted); font-size: 14px; }
        .btn {
            display: block; width: 100%; border: 0; border-radius: 12px; padding: 16px;
            font-size: 17px; font-weight: 600; background: var(--accent); color: #fff; cursor: pointer;
        }
        .btn:disabled { opacity: .5; }
        .btn-ghost { background: transparent; border: 1px solid var(--line); color: var(--text); }
        .alert { border-radius: 12px; padding: 14px 16px; margin-top: 16px; font-weight: 500; }
        .alert-ok { background: rgba(34,197,94,.15); border: 1px solid var(--ok); }
        .alert-err { background: rgba(239,68,68,.15); border: 1px solid var(--err); }
        .alert-info { background: rgba(148,163,184,.12); border: 1px solid var(--line); }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; }
        .badge { font-size: 12px; padding: 3px 8px; border-radius: 999px; background: var(--line); }
        .badge-ok { background: rgba(34,197,94,.2); color: var(--ok); }
        .badge-warn { background: rgba(245,158,11,.2); color: var(--warn); }
        .badge-err { background: rgba(239,68,68,.2); color: var(--err); }

        /* Halaman lebar (admin) */
        .wrap-wide { max-width: 1000px; }

        /* Navigasi */
        .nav { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 20px; padding-bottom: 2px; }
        .nav a {
            white-space: nowrap; text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 600;
            padding: 8px 14px; border-radius: 999px; border: 1px solid var(--line);
        }
        .nav a.active { color: #fff; background: var(--accent); border-color: var(--accent); }

        /* Judul & filter */
        .head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .head h1 { margin: 0; font-size: 22px; }
        .filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .input {
            background: var(--card); color: var(--text); border: 1px solid var(--line); border-radius: 10px;
            padding: 9px 12px; font-size: 15px; font-family: inherit; color-scheme: dark;
        }
        .btn-sm { display: inline-block; width: auto; padding: 9px 14px; font-size: 14px; border-radius: 10px; text-decoration: none; text-align: center; }

        /* Kartu angka */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 16px; }
        .stat { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px; }
        .stat .num { font-size: 26px; font-weight: 700; margin-top: 4px; }

        /* Tabel */
        .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 14px; background: var(--card); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        th { color: var(--muted); font-weight: 600; font-size: 13px; }
        tr:last-child td { border-bottom: 0; }
        td a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .empty { padding: 24px; text-align: center; color: var(--muted); }

        [hidden] { display: none !important; }
    </style>
    @yield('head')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
