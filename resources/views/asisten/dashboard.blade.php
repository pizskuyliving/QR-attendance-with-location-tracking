@extends('layouts.asisten')
@section('title', 'Dashboard Asisten — FLEX')
@section('content')
    <div class="row between" style="margin-bottom:12px">
        <div>
            <h1 style="margin:0">Kelas yang diampu</h1>
            <p class="muted" style="margin:0">Halo, {{ auth()->user()->name }}. Pilih kelas untuk menyalakan QR absen atau melihat rekap.</p>
        </div>
        <button class="btn accent" onclick="document.getElementById('dlgKelas').showModal()">+ Tambah kelas</button>
    </div>
    @if (auth()->user()->role === 'admin' && ($jumlahMenunggu = \App\Models\User::where('status', 'menunggu')->count()))
        <div class="alert info">{!! \App\Support\Ikon::svg('user-plus') !!} Ada <b>{{ $jumlahMenunggu }}</b> pendaftaran asisten menunggu persetujuan. <a href="{{ route('asisten.profil') }}#persetujuan">Tinjau sekarang</a></div>
    @endif
    @unless (auth()->user()->pakaiDuaLangkah())
        <div class="alert info">{!! \App\Support\Ikon::svg('lock') !!} Akunmu belum memakai verifikasi dua langkah. <a href="{{ route('asisten.profil') }}#dua-langkah">Aktifkan 2FA</a> agar dashboard tetap aman walaupun kata sandi bocor.</div>
    @endunless

    <div class="cards">
        @forelse ($kelas as $k)
            <a class="card kelas-card" href="{{ route('asisten.kelas.show', $k['kelas']) }}">
                <div class="row between">
                    <span class="badge grey">Kelas {{ $k['kelas']->nama_kelas }}</span>
                    @if ($k['sesiAktif'])<span class="badge live">QR aktif · P{{ $k['sesiAktif']->pertemuan }}</span>@endif
                </div>
                <div class="mk" style="margin-top:8px">{{ $k['kelas']->mata_kuliah }}</div>
                <div class="small muted">
                    {{ collect([$k['kelas']->hari, $k['kelas']->ruang])->filter()->implode(' · ') ?: ' ' }}
                    @if (auth()->user()->role === 'admin') · {{ $k['kelas']->asisten->name }} @endif
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
                <p class="muted">Tambahkan kelas dan mata kuliah yang Anda ampu, lalu import data mahasiswa dari Excel.</p>
                <button class="btn" onclick="document.getElementById('dlgKelas').showModal()">+ Tambah kelas</button>
            </div>
        @endforelse
    </div>

    <dialog id="dlgKelas">
        <form method="post" action="{{ route('asisten.kelas.store') }}">
            @csrf
            <h2>Tambah kelas</h2>
            @include('asisten._form_kelas', ['k' => null])
            <div class="row" style="justify-content:flex-end">
                <button type="button" class="btn ghost" onclick="this.closest('dialog').close()">Batal</button>
                <button class="btn">Simpan</button>
            </div>
        </form>
    </dialog>
@endsection
