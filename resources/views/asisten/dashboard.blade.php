@extends('layouts.asisten')
@section('title', 'Dashboard Asisten — FLEX')
@section('content')
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Kelas yang diampu</h1>
            <p class="muted" style="margin:0">Halo, {{ auth()->user()->name }}. Pilih kelas untuk menyalakan QR absen atau melihat rekap. Kelas ditambahkan oleh dosen pengampu.</p>
        </div>
        @if (auth()->user()->role === 'admin')<a class="btn accent" href="{{ route('asisten.matkul.index') }}">Kelola matkul &amp; kelas</a>@endif
    </div>
    @if (auth()->user()->role === 'admin' && ($jumlahMenunggu = \App\Models\User::where('status', 'menunggu')->count()))
        <div class="alert info">{!! \App\Support\Ikon::svg('user-plus') !!} Ada <b>{{ $jumlahMenunggu }}</b> pendaftaran asisten menunggu persetujuan. <a href="{{ route('asisten.laboran.akun') }}#persetujuan">Tinjau sekarang</a></div>
    @endif
    @unless (auth()->user()->pakaiDuaLangkah())
        <div class="alert info">{!! \App\Support\Ikon::svg('lock') !!} Akunmu belum memakai verifikasi dua langkah. <a href="{{ route('asisten.profil') }}#dua-langkah">Aktifkan 2FA</a> agar dashboard tetap aman walaupun kata sandi bocor.</div>
    @endunless

    <div class="cards">
        @forelse ($kelas as $k)
            <a class="card kelas-card" href="{{ route('asisten.kelas.show', $k['kelas']) }}">
                <div class="row between">
                    <span class="badge grey">Kelas {{ $k['kelas']->nama_kelas }}</span>
                    <span class="row" style="gap:6px">
                        @if ($k['kelas']->catatan_baru_count && $k['kelas']->diajarOleh(auth()->user()))<span class="badge I">Pesan baru</span>@endif
                        @if ($k['sesiAktif'])<span class="badge live">QR aktif · P{{ $k['sesiAktif']->pertemuan }}</span>@endif
                    </span>
                </div>
                <div class="mk" style="margin-top:8px">{{ $k['kelas']->mata_kuliah }}</div>
                <div class="small muted">
                    {{ collect([$k['kelas']->hari, $k['kelas']->ruang])->filter()->implode(' · ') ?: ' ' }}
                    @if ($k['kelas']->matkul) · Dosen {{ $k['kelas']->matkul->dosen->name }} @endif
                    @if (auth()->user()->role === 'admin') · {{ $k['kelas']->namaAsisten() }} @endif
                </div>
                <div class="stat">
                    <div><b>{{ $k['kelas']->mahasiswa_count }}</b><span>mahasiswa</span></div>
                    <div><b>{{ $k['terlaksana'] }}/{{ $k['kelas']->jumlah_pertemuan }}</b><span>pertemuan</span></div>
                    <div><b>{{ $k['rata'] }}%</b><span>rata-rata hadir</span></div>
                </div>
            </a>
        @empty
            <div class="card">
                <h3>Belum ada kelas</h3>
                <p class="muted" style="margin-bottom:0">Kelas dibuat oleh dosen pengampu mata kuliah. Setelah dosen memilihmu sebagai asisten sebuah kelas, kelas itu muncul di sini dan kamu mendapat email pemberitahuan.</p>
            </div>
        @endforelse
    </div>

@endsection
