@extends('layouts.asisten')
@section('title', 'Akun — FLEX')
@php($saya = auth()->user())
@section('content')
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Akun</h1>
            <p class="muted" style="margin:0">Persetujuan asisten, daftar akun laboran, dosen, dan asisten, serta tambah akun baru.</p>
        </div>
        <a class="btn accent" href="#tambah">+ Tambah akun</a>
    </div>

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
            <p class="small muted" style="margin-bottom:0">Tidak ada pendaftaran baru. Asisten bisa mendaftar sendiri lewat "Daftar akun asisten" di halaman masuk{{ config('absen.daftar_asisten') ? '' : ' (saat ini ditutup di menu Sistem)' }}.</p>
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

    <div class="card" id="kelola-akun">
        <h2>Kelola akun</h2>
        <nav class="tabs" style="margin-bottom:12px">
            @foreach ([null => 'Semua', 'admin' => 'Laboran', 'dosen' => 'Dosen', 'asisten' => 'Asisten'] as $k => $v)
                <a href="{{ route('asisten.laboran.akun', array_filter(['peran' => $k ?: null, 'cari' => $cari ?: null])) }}#kelola-akun" class="{{ $peran === ($k ?: null) ? 'on' : '' }}">{{ $v }} <span class="muted">{{ $k ? ($jumlahPeran[$k] ?? 0) : $jumlahPeran->sum() }}</span></a>
            @endforeach
        </nav>
        <form method="get" action="{{ route('asisten.laboran.akun') }}#kelola-akun" class="row cari-dosen">
            @if ($peran)<input type="hidden" name="peran" value="{{ $peran }}">@endif
            <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama atau email">
            <button class="btn soft">Cari</button>
        </form>
        @if ($akun->isEmpty())
            <p class="empty">Tidak ada akun yang cocok.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th class="c">Mengajar</th><th class="c">2FA</th><th>Aksi</th></tr></thead>
                <tbody>
                @foreach ($akun as $a)
                    <tr>
                        <td>{{ $a->name }}@if ($a->id === $saya->id) <span class="badge grey">kamu</span>@endif</td><td>{{ $a->email }}</td><td>{{ $a->peranLabel() }}</td>
                        <td class="c">{{ match ($a->role) { 'dosen' => $a->matkul_count.' matkul', 'asisten' => ($a->kelas_count + $a->kelas_kedua_count).' kelas', default => '–' } }}</td>
                        <td class="c">{!! $a->pakaiDuaLangkah() ? '<span class="badge H">aktif</span>' : '<span class="badge grey">tidak</span>' !!}</td>
                        <td class="row" style="flex-wrap:nowrap;gap:12px">
                            <form method="post" action="{{ route('asisten.akun.reset', $a) }}" class="row" style="flex-wrap:nowrap" data-konfirmasi="{{ 'Kata sandi baru untuk '.$a->name.' (min. 8 karakter, huruf dan angka):' }}" data-tanya="password" data-min="8" data-ok="Simpan" data-placeholder="Kata sandi baru">
                                @csrf @method('put')<input type="hidden" name="password"><button class="link-btn">Ganti sandi</button>
                            </form>
                            @if ($a->pakaiDuaLangkah() && $a->id !== $saya->id)
                            <form method="post" action="{{ route('asisten.akun.2fa', $a) }}" data-konfirmasi="{{ 'Reset 2FA '.$a->name.'? Lakukan hanya bila dia kehilangan HP dan kode cadangan.' }}">@csrf @method('delete')<button class="link-btn">Reset 2FA</button></form>
                            @endif
                            @if ($a->id !== $saya->id)
                            <form method="post" action="{{ route('asisten.akun.destroy', $a) }}" data-konfirmasi="{{ 'Hapus akun '.$a->name.'? '.match ($a->role) { 'dosen' => 'Mata kuliahnya ikut terhapus; kelasnya tetap ada sebagai kelas lama.', 'asisten' => 'Kelas di mata kuliah dosen tetap ada tanpa asisten; kelas lama yang dibuatnya sendiri ikut terhapus.', default => '' } }}">@csrf @method('delete')<button class="link-btn bad">Hapus</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="card" id="tambah">
        <h2>Tambah akun</h2>
        <p class="small muted" style="margin-top:0">Akun yang dibuat laboran langsung aktif. Berikan kata sandi awal ke pemiliknya dan minta diganti di Profil.</p>
        <form method="post" action="{{ route('asisten.akun.store') }}">
            @csrf
            <div class="grid cols-2" style="gap:0 12px">
                <div class="field"><label>Nama</label><input name="name" value="{{ old('name') }}" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="field"><label>Kata sandi awal</label><input name="password" minlength="8" required></div>
                <div class="field"><label>Peran</label>
                    <select name="role">
                        @foreach (['asisten' => 'Asisten dosen', 'dosen' => 'Dosen', 'admin' => 'Laboran'] as $k => $v)
                            <option value="{{ $k }}" @selected(old('role', $tambahPeran) === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn">Tambah akun</button>
        </form>
    </div>
@endsection
