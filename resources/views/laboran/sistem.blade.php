@extends('layouts.asisten')
@section('title', 'Sistem — FLEX')
@php($c = config('absen'))
@section('content')
    <h1 style="margin:0">Pengaturan sistem</h1>
    <p class="muted" style="margin:0 0 12px">Berlaku untuk semua pengguna. Perubahan langsung dipakai setelah disimpan.</p>

    <div class="grid cols-2 sistem">
        <form method="post" action="{{ route('asisten.laboran.sistem.simpan') }}" class="card">
            @csrf @method('put')
            <h2>Umum</h2>
            <div class="field"><label>Nama institusi</label>
                <input name="institusi" value="{{ old('institusi', $c['institusi']) }}" maxlength="150" required>
                <div class="small muted" style="margin-top:4px">Tampil di footer, email, dan aplikasi authenticator.</div>
            </div>
            <div class="field"><label>Domain email mahasiswa</label>
                <input name="domain_email" value="{{ old('domain_email', implode(', ', $c['domain_email'])) }}" placeholder="student.uir.ac.id">
                <div class="small muted" style="margin-top:4px">Pisahkan dengan koma bila lebih dari satu. Kosongkan untuk menerima semua email.</div>
            </div>

            <h2 style="margin-top:18px">Pendaftaran</h2>
            <div class="field"><label class="check"><input type="checkbox" name="daftar_mahasiswa" value="1" @checked(old('daftar_mahasiswa', $c['daftar_mahasiswa']))> Mahasiswa boleh mendaftar sendiri</label></div>
            <div class="field"><label class="check"><input type="checkbox" name="daftar_asisten" value="1" @checked(old('daftar_asisten', $c['daftar_asisten']))> Asisten boleh mendaftar sendiri (tetap menunggu persetujuan laboran)</label></div>

            <h2 style="margin-top:18px">Unggahan</h2>
            <div class="field"><label>Ukuran file maksimal (MB)</label>
                <input type="number" name="maks_unggah_mb" min="1" max="500" value="{{ old('maks_unggah_mb', intdiv($c['maks_unggah_kb'], 1024)) }}" style="max-width:140px" required>
            </div>

            @if ($phpUnggahKb < $c['maks_unggah_kb'])
                <div class="alert info">{!! \App\Support\Ikon::svg('alert') !!} <span>Server PHP saat ini hanya menerima file sampai {{ round($phpUnggahKb / 1024) }} MB. Naikkan <code>upload_max_filesize</code> dan <code>post_max_size</code> di php.ini agar batas di atas berlaku.</span></div>
            @endif

            <h2 style="margin-top:18px">Nilai huruf</h2>
            <p class="small muted" style="margin-top:0">Batas bawah nilai akhir untuk tiap huruf. Di bawah batas D menjadi E.</p>
            <div class="huruf">
                @foreach (['A', 'B', 'C', 'D'] as $h)
                    <label><span>{{ $h }} ≥</span><input type="number" name="huruf[{{ $h }}]" min="1" max="100" value="{{ old('huruf.'.$h, $c['huruf'][$h] ?? '') }}" required></label>
                @endforeach
            </div>

            <button class="btn" style="margin-top:16px">Simpan pengaturan</button>
        </form>

        <div>
            <div class="card">
                <h2>Ringkasan data</h2>
                <dl class="dl">
                    @foreach ($jumlah as $label => $n)
                        <dt>{{ $label }}</dt><dd>{{ $n }}</dd>
                    @endforeach
                </dl>
                <div class="row" style="gap:8px;margin-top:12px">
                    <a class="btn soft sm" href="{{ route('asisten.laboran.dosen') }}">Daftar dosen</a>
                    <a class="btn soft sm" href="{{ route('asisten.laboran.akun') }}">Kelola akun</a>
                </div>
            </div>
            <div class="card">
                <h2>Server</h2>
                <dl class="dl">
                    <dt>Versi {{ $c['nama'] }}</dt><dd>{{ $c['versi'] }}</dd>
                    <dt>PHP</dt><dd>{{ PHP_VERSION }}</dd>
                    <dt>Database</dt><dd>{{ config('database.default') }} · {{ basename((string) config('database.connections.'.config('database.default').'.database')) }}</dd>
                    <dt>Email</dt><dd>{{ config('mail.default') }}@if (config('mail.default') === 'log') <span class="small muted">(belum benar-benar terkirim)</span>@endif</dd>
                    <dt>Zona waktu</dt><dd>{{ config('app.timezone') }}</dd>
                    <dt>Mode</dt><dd>{{ config('app.env') }}{{ config('app.debug') ? ' · debug aktif' : '' }}</dd>
                </dl>
                <p class="small muted" style="margin-bottom:0">Pengaturan ini dibaca dari file <code>.env</code> di server dan tidak bisa diubah dari aplikasi.</p>
            </div>
        </div>
    </div>
@endsection
