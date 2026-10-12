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
                <p class="small muted">Peran: {{ $user->role === 'admin' ? 'Admin' : 'Asisten dosen' }}</p>
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
        <div class="card" id="persetujuan">
            <div class="row between">
                <h2 style="margin:0">Pendaftaran asisten menunggu persetujuan</h2>
                <span class="badge {{ $menunggu->count() ? 'I' : 'grey' }}">{{ $menunggu->count() }}</span>
            </div>
            @if ($menunggu->count())
                <div class="table-wrap" style="margin-top:12px">
                    <table>
                        <thead><tr><th>Didaftarkan</th><th>Nama</th><th>Email</th><th>Mata kuliah yang diampu</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @foreach ($menunggu as $m)
                            <tr>
                                <td style="white-space:nowrap">{{ $m->created_at->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $m->name }}</td><td>{{ $m->email }}</td><td>{{ $m->keterangan }}</td>
                                <td class="row" style="flex-wrap:nowrap;gap:8px">
                                    <form method="post" action="{{ route('asisten.akun.setujui', $m) }}">@csrf<button class="btn sm">Setujui</button></form>
                                    <form method="post" action="{{ route('asisten.akun.tolak', $m) }}" data-konfirmasi="{{ 'Tolak pendaftaran '.$m->name.'?' }}">@csrf<button class="btn danger sm">Tolak</button></form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="small muted" style="margin-bottom:0">Tidak ada pendaftaran baru. Asisten bisa mendaftar sendiri lewat tautan "Daftar akun asisten" di halaman masuk asisten.</p>
            @endif
            @if ($ditolak->count())
                <details style="margin-top:12px">
                    <summary class="small">Pendaftaran yang ditolak ({{ $ditolak->count() }})</summary>
                    <div class="table-wrap" style="margin-top:8px"><table><tbody>
                    @foreach ($ditolak as $m)
                        <tr><td>{{ $m->name }}</td><td>{{ $m->email }}</td><td class="small muted">{{ $m->keterangan }}</td>
                            <td class="row" style="flex-wrap:nowrap;gap:12px">
                                <form method="post" action="{{ route('asisten.akun.setujui', $m) }}">@csrf<button class="link-btn">Setujui</button></form>
                                <form method="post" action="{{ route('asisten.akun.destroy', $m) }}" data-konfirmasi="{{ 'Hapus data pendaftaran '.$m->name.'? Email ini bisa dipakai mendaftar lagi.' }}">@csrf @method('delete')<button class="link-btn bad">Hapus</button></form>
                            </td></tr>
                    @endforeach
                    </tbody></table></div>
                </details>
            @endif
        </div>

        <div class="card">
            <h2>Kelola akun asisten</h2>
            <div class="table-wrap" style="margin-bottom:16px">
                <table>
                    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="c">Kelas</th><th class="c">2FA</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($asisten as $a)
                        <tr>
                            <td>{{ $a->name }}</td><td>{{ $a->email }}</td><td>{{ $a->role }}</td><td class="c">{{ $a->kelas_count }}</td>
                            <td class="c">{!! $a->pakaiDuaLangkah() ? '<span class="badge H">aktif</span>' : '<span class="badge grey">tidak</span>' !!}</td>
                            <td class="row" style="flex-wrap:nowrap;gap:12px">
                                <form method="post" action="{{ route('asisten.akun.reset', $a) }}" class="row" style="flex-wrap:nowrap" data-konfirmasi="{{ 'Kata sandi baru untuk '.$a->name.' (min. 8 karakter, huruf dan angka):' }}" data-tanya="password" data-min="8" data-ok="Simpan" data-placeholder="Kata sandi baru">
                                    @csrf @method('put')<input type="hidden" name="password"><button class="link-btn">Ganti sandi</button>
                                </form>
                                @if ($a->pakaiDuaLangkah() && $a->id !== $user->id)
                                <form method="post" action="{{ route('asisten.akun.2fa', $a) }}" data-konfirmasi="{{ 'Reset 2FA '.$a->name.'? Lakukan hanya bila dia kehilangan HP dan kode cadangan.' }}">@csrf @method('delete')<button class="link-btn">Reset 2FA</button></form>
                                @endif
                                @if ($a->id !== $user->id)
                                <form method="post" action="{{ route('asisten.akun.destroy', $a) }}" data-konfirmasi="{{ 'Hapus akun '.$a->name.' beserta semua kelasnya?' }}">@csrf @method('delete')<button class="link-btn bad">Hapus</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <h3>Tambah akun</h3>
            <form method="post" action="{{ route('asisten.akun.store') }}">
                @csrf
                <div class="grid cols-2" style="gap:0 12px">
                    <div class="field"><label>Nama</label><input name="name" required></div>
                    <div class="field"><label>Email</label><input type="email" name="email" required></div>
                    <div class="field"><label>Kata sandi awal</label><input name="password" minlength="8" required></div>
                    <div class="field"><label>Peran</label><select name="role"><option value="asisten">Asisten dosen</option><option value="admin">Admin</option></select></div>
                </div>
                <button class="btn">Tambah akun</button>
            </form>
        </div>
    @endif
@endsection
