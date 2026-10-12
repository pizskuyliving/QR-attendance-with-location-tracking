@php($c = config('absen'))
@if ($ringkas ?? false)
    <div class="auth-tema">@include('layouts.tema', ['temaKelas' => 'ikon', 'temaLabel' => null])</div>
    <footer class="foot-mini">© {{ date('Y') }} {{ $c['institusi'] }} · {{ $c['nama'] }} v{{ $c['versi'] }}</footer>
@else
    <footer class="site-foot">
        <div class="foot-in">
            <div class="foot-top">
                <div class="foot-brand">
                    <img src="/icons/icon-192.png" alt="">
                    <div><b>{{ $c['nama'] }}</b><span>{{ $c['nama_panjang'] }}</span></div>
                </div>
                <p>Absensi QR, materi perkuliahan, tugas, dan rekap nilai dalam satu tempat.</p>
            </div>
            <div class="foot-bottom">
                <span>© {{ date('Y') }} {{ $c['institusi'] }}. Hak cipta dilindungi.</span>
                <span>{{ $c['nama'] }} versi {{ $c['versi'] }}</span>
            </div>
        </div>
    </footer>
@endif
