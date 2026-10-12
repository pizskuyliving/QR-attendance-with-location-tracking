@extends('layouts.mahasiswa')
@section('title', 'Pilih Mata Kuliah — FLEX')
@section('head')
    <h1 style="margin:0">Mata Kuliah</h1>
    <div class="sub">Kelas {{ $mhs->kelas ?: '-' }} · pilih semua mata kuliah yang kamu ikuti.</div>
@endsection
@section('content')
    <div class="sec-title"><h2>Kelas {{ $mhs->kelas ?: 'kamu' }}</h2></div>
    @if ($kelasku->count())
        <div class="cards" style="margin-bottom:16px">@foreach ($kelasku as $k)@include('mahasiswa._kelas_item', ['k' => $k])@endforeach</div>
    @else
        <div class="card empty">Belum ada mata kuliah untuk kelas {{ $mhs->kelas }}. Tunggu asisten membuatnya.</div>
    @endif

    @if ($kelasLain->count())
        <details class="card" @if (! $kelasku->count() || collect($diikuti)->diff($kelasku->pluck('id'))->count()) open @endif>
            <summary><b>Kelas lain</b> <span class="small muted">(mengulang / ikut matkul di kelas lain)</span></summary>
            @foreach ($kelasLain as $nama => $daftar)
                <div class="small muted" style="margin:14px 0 6px;font-weight:700">Kelas {{ $nama }}</div>
                <div class="cards">@foreach ($daftar as $k)@include('mahasiswa._kelas_item', ['k' => $k])@endforeach</div>
            @endforeach
        </details>
    @endif

    <a class="btn only-mobile block" href="{{ route('mhs.home') }}">Selesai</a>
@endsection
