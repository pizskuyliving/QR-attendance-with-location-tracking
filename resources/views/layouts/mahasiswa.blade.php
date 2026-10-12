@extends('layouts.base')
@section('body-class', 'mhs')
@php($saya = auth('mahasiswa')->user())
@php($ik = fn ($n) => \App\Support\Ikon::svg($n, ''))
@php($menu = [
    ['mhs.home', 'Beranda', 'home', ['mhs.home']],
    ['mhs.kelas', 'Mata Kuliah', 'buku', ['mhs.kelas', 'mhs.matkul', 'mhs.tugas']],
    ['mhs.riwayat', 'Riwayat', 'jam', ['mhs.riwayat']],
    ['mhs.akun', 'Akun', 'user', ['mhs.akun']],
])
@section('body')
<div class="mhs-shell">
    <aside class="mhs-side">
        <a class="mhs-brand" href="{{ route('mhs.home') }}"><img src="/icons/icon-192.png" alt=""><span>FLEX<small>Flexible Learning Experience</small></span></a>
        <a class="btn accent block" href="{{ route('mhs.scan') }}">{!! $ik('scan') !!}Scan QR Absen</a>
        <nav>
            @foreach ($menu as [$r, $label, $i, $aktif])
                <a href="{{ route($r) }}" class="{{ request()->routeIs(...$aktif) ? 'on' : '' }}">{!! $ik($i) !!}{{ $label }}</a>
            @endforeach
        </nav>
        @if ($saya)
            <div class="mhs-me">
                <span class="avatar">{{ \Illuminate\Support\Str::of($saya->nama)->explode(' ')->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode('') }}</span>
                <span><b>{{ $saya->nama }}</b><small><span class="mono">{{ $saya->npm }}</span>@if ($saya->kelas) · Kelas {{ $saya->kelas }}@endif</small></span>
            </div>
        @endif
        @include('layouts.tema', ['temaKelas' => 'block', 'temaLabel' => 'Ganti tema'])
    </aside>
    <div class="mhs-main">
        <header class="mhs-head"><div class="mhs-inner">@yield('head')</div></header>
        <main class="mhs-inner mhs-body">
            @include('layouts.flash')
            @yield('content')
        </main>
        @include('layouts.footer')
    </div>
</div>
<nav class="bottomnav">
    @foreach ([$menu[0], $menu[1], null, $menu[2], $menu[3]] as $m)
        @if ($m === null)
            <a href="{{ route('mhs.scan') }}" class="scan {{ request()->routeIs('mhs.scan') ? 'on' : '' }}" aria-label="Scan QR absen"><span class="scan-ring">{!! $ik('scan') !!}</span>Scan</a>
        @else
            @php([$r, $label, $i, $aktif] = $m)
            <a href="{{ route($r) }}" class="{{ request()->routeIs(...$aktif) ? 'on' : '' }}">{!! $ik($i) !!}{{ $label === 'Mata Kuliah' ? 'Matkul' : $label }}</a>
        @endif
    @endforeach
</nav>
@endsection
