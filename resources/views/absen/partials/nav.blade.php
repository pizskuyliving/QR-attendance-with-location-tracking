@php($isAdmin = auth()->user()?->role === 'admin')
<nav class="nav">
    <a href="{{ route('absen.scan') }}" class="{{ request()->routeIs('absen.scan') ? 'active' : '' }}">Absen</a>
    <a href="{{ route('absen.riwayat') }}" class="{{ request()->routeIs('absen.riwayat') ? 'active' : '' }}">Riwayat Saya</a>
    @if ($isAdmin)
        <a href="{{ route('admin.harian') }}" class="{{ request()->routeIs('admin.harian') ? 'active' : '' }}">Absensi Harian</a>
        <a href="{{ route('admin.rekap') }}" class="{{ request()->routeIs('admin.rekap') ? 'active' : '' }}">Rekap Bulanan</a>
        <a href="{{ route('admin.pengaturan') }}" class="{{ request()->routeIs('admin.pengaturan') ? 'active' : '' }}">Pengaturan</a>
        <a href="{{ route('qr.display', auth()->user()->office_id ?? 1) }}" target="_blank">Layar QR ↗</a>
    @endif
</nav>
