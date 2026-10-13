@extends('layouts.asisten')
@section('title', 'Profil — FLEX')
@section('content')
    <h1>Profil</h1>
    <div class="grid cols-2">
        <div class="card">
            <h2>Data akun</h2>
            <form method="post" action="{{ route('asisten.profil.update') }}">
                @csrf @method('put')
                <div class="field"><label>Nama</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
                <p class="small muted">Peran: {{ $user->peranLabel() }}</p>
                <button class="btn">Simpan</button>
            </form>
        </div>
        <div class="card">
            <h2>Ganti kata sandi</h2>
            <form method="post" action="{{ route('asisten.sandi') }}">
                @csrf
                <div class="field"><label>Kata sandi lama</label><input type="password" name="password_lama" required></div>
                <div class="field"><label>Kata sandi baru (min. 8 karakter, huruf dan angka)</label><input type="password" name="password" minlength="8" required></div>
                <div class="field"><label>Ulangi kata sandi baru</label><input type="password" name="password_confirmation" required></div>
                <button class="btn">Ganti kata sandi</button>
            </form>
        </div>
    </div>

    @include('layouts.tema-pilih')

    <div class="card" id="dua-langkah">
        <div class="row between">
            <h2 style="margin:0">Verifikasi dua langkah (2FA)</h2>
            @if ($user->pakaiDuaLangkah())<span class="badge H">Aktif sejak {{ $user->dua_langkah_aktif_at->translatedFormat('d M Y') }}</span>@else<span class="badge A">Belum aktif</span>@endif
        </div>

        @if (session('kode_cadangan'))
            <div class="alert info" style="margin-top:14px">
                <b>Kode cadangan (hanya ditampilkan sekali).</b> Simpan di tempat aman, misalnya dicetak atau dicatat. Pakai salah satunya bila HP hilang; setiap kode hanya berlaku sekali.
                <pre id="kodeCadangan" style="font-size:1rem;letter-spacing:1px;margin:10px 0;columns:2">{{ implode("\n", session('kode_cadangan')) }}</pre>
                <button type="button" class="btn ghost sm" onclick="navigator.clipboard.writeText(document.getElementById('kodeCadangan').textContent).then(() => this.textContent = 'Tersalin ✓')">Salin</button>
            </div>
        @endif

        @if ($user->pakaiDuaLangkah())
            <p class="small muted">Setiap masuk ke dashboard, selain kata sandi kamu diminta kode 6 angka dari aplikasi authenticator. Sisa kode cadangan: <b>{{ count($user->dua_langkah_cadangan ?? []) }}</b>.</p>
            @error('password_2fa')<div class="alert bad">{{ $message }}</div>@enderror
            <div class="grid cols-2" style="gap:16px">
                <form method="post" action="{{ route('asisten.2fa.cadangan') }}" class="stack">
                    @csrf
                    <b class="small">Buat kode cadangan baru</b>
                    <input type="password" name="password" placeholder="Kata sandi" required>
                    <button class="btn ghost sm">Buat ulang kode cadangan</button>
                </form>
                <form method="post" action="{{ route('asisten.2fa.matikan') }}" class="stack" data-konfirmasi="Matikan verifikasi dua langkah? Akun jadi hanya dilindungi kata sandi.">
                    @csrf @method('delete')
                    <b class="small">Matikan 2FA</b>
                    <input type="password" name="password" placeholder="Kata sandi" required>
                    <input name="kode" inputmode="numeric" placeholder="Kode 6 angka" required>
                    <button class="btn danger sm">Matikan</button>
                </form>
            </div>
        @elseif ($calon = session('dua_langkah_calon'))
            @php($uri = \App\Support\Totp::uri($calon, $user->email, config('absen.nama')))
            <ol class="small" style="padding-left:18px">
                <li>Pasang <b>Google Authenticator</b> atau <b>Microsoft Authenticator</b> di HP (gratis).</li>
                <li>Di aplikasi, tekan <b>+</b> lalu <b>Pindai kode QR</b>, arahkan ke QR ini.</li>
                <li>Masukkan kode 6 angka yang muncul di aplikasi, lalu tekan Aktifkan.</li>
            </ol>
            <div class="row" style="gap:20px;align-items:flex-start">
                <div id="qr2fa" style="background:#fff;padding:10px;border-radius:12px;border:1px solid var(--line)" data-uri="{{ $uri }}"></div>
                <div class="stack" style="flex:1;min-width:220px">
                    <div class="small muted">Tidak bisa memindai? Masukkan kunci ini secara manual (jenis: berbasis waktu):</div>
                    <code style="word-break:break-all;font-size:1rem">{{ trim(chunk_split($calon, 4, ' ')) }}</code>
                    <form method="post" action="{{ route('asisten.2fa.konfirmasi') }}" class="stack" style="margin-top:8px">
                        @csrf
                        <input name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="Kode 6 angka" required autofocus style="font-size:1.15rem;letter-spacing:4px">
                        <div class="row"><button class="btn">Aktifkan</button></div>
                    </form>
                    <form method="post" action="{{ route('asisten.2fa.batal') }}">@csrf<button class="link-btn small">Batal</button></form>
                </div>
            </div>
            <script src="/vendor/qrcode.js"></script>
            <script>
                (() => {
                    const el = document.getElementById('qr2fa');
                    const qr = qrcode(0, 'M'); qr.addData(el.dataset.uri); qr.make();
                    el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0 });
                })();
            </script>
        @else
            <p class="small muted">Lindungi akun dengan kode 6 angka dari aplikasi authenticator di HP. Walaupun kata sandi bocor, orang lain tetap tidak bisa masuk tanpa HP-mu.</p>
            <form method="post" action="{{ route('asisten.2fa.siapkan') }}">@csrf<button class="btn">Aktifkan 2FA</button></form>
        @endif
    </div>

    @if ($user->role === 'admin')
        <div class="alert info">{!! \App\Support\Ikon::svg('user') !!} Persetujuan asisten, kelola akun, dan tambah akun ada di menu <a href="{{ route('asisten.laboran.akun') }}">Akun</a>.</div>
    @endif
@endsection
