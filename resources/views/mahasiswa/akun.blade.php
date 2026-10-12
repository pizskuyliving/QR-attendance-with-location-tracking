@extends('layouts.mahasiswa')
@section('title', 'Akun — FLEX')
@section('head')
    <h1 style="margin:0">Akun</h1>
    <div class="sub">Profil, kata sandi, dan HP untuk absen</div>
@endsection
@section('content')
    <div class="lay">
        <div>
            <div class="card">
                <div class="row" style="gap:14px;flex-wrap:nowrap;margin-bottom:14px">
                    <span class="avatar" style="width:52px;height:52px;font-size:1rem">{{ \Illuminate\Support\Str::of($mhs->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('') }}</span>
                    <div style="min-width:0"><div class="mk-title">{{ $mhs->nama }}</div><div class="meta">Mahasiswa</div></div>
                </div>
                <dl class="dl">
                    <dt>NPM</dt><dd>{{ $mhs->npm }}</dd>
                    <dt>Email</dt><dd>{{ $mhs->email }}</dd>
                    <dt>Kelas</dt><dd>{{ $mhs->kelas ?: '–' }}</dd>
                    <dt>Mata kuliah</dt><dd>{{ $mhs->kelas()->count() }} diikuti · <a href="{{ route('mhs.kelas') }}">atur</a></dd>
                </dl>
            </div>
            <div class="card">
                <h2 style="font-size:1rem">Ganti kata sandi</h2>
                <form method="post" action="{{ route('mhs.sandi') }}">
                    @csrf
                    <div class="field"><label>Kata sandi lama</label><input type="password" name="password_lama" required autocomplete="current-password"></div>
                    <div class="grid cols-2" style="gap:0 12px">
                        <div class="field"><label>Kata sandi baru</label><input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="min. 8 karakter, huruf dan angka"></div>
                        <div class="field"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>
                    </div>
                    <button class="btn">Simpan kata sandi</button>
                </form>
            </div>
        </div>
        <div class="side">
            <div class="card">
                <h2 style="font-size:1rem">HP untuk absen</h2>
                @if ($mhs->perangkat)
                    <p class="small" style="margin:0"><span class="badge H">Terikat</span> sejak {{ $mhs->perangkat_terikat_at?->translatedFormat('d M Y H:i') }}</p>
                    <p class="meta" style="margin:10px 0 0">Absen hanya bisa dari HP itu. Kalau ganti HP, ganti browser, atau menghapus data browser, minta asisten menekan <b>Reset HP</b>.</p>
                @else
                    <p class="meta" style="margin:0">Belum ada HP yang terikat. Scan QR pertama akan mengikat akunmu ke HP yang dipakai saat itu, jadi pakai HP sendiri.</p>
                @endif
            </div>
            @include('layouts.tema-pilih')
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost block" style="color:var(--bad)">Keluar dari akun</button></form>
        </div>
    </div>
@endsection
