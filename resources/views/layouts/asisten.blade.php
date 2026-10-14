@extends('layouts.base')
@section('body')
<div class="page-col">
    @php($peran = auth()->user()->role)
    @php($menu = array_values(array_filter([
        $peran !== 'dosen' ? ['asisten.dashboard', 'Kelas', 'kelas', ['asisten.dashboard', 'asisten.kelas.*']] : null,
        $peran === 'asisten' ? ['asisten.pesan', 'Pesan', 'surat', ['asisten.pesan']] : null,
        $peran === 'dosen' ? ['asisten.matkul.index', 'Matkul', 'buku', ['asisten.matkul.*']] : null,
        $peran === 'admin' ? ['asisten.laboran.dosen', 'Dosen', 'toga', ['asisten.laboran.dosen', 'asisten.matkul.*']] : null,
        $peran === 'admin' ? ['asisten.laboran.akun', 'Akun', 'akun', ['asisten.laboran.akun']] : null,
        $peran === 'admin' ? ['asisten.laboran.sistem', 'Sistem', 'gear', ['asisten.laboran.sistem']] : null,
        ['asisten.profil', 'Profil', 'user', ['asisten.profil']],
    ])))
    {{-- menu 3 atau lebih: di HP pindah ke bilah bawah supaya bilah atas tidak berdesakan --}}
    @php($navBawah = count($menu) > 2)
    @php($pesanBaru = $peran === 'asisten' ? \App\Http\Controllers\Asisten\PesanController::jumlahBaru(auth()->id()) : 0)
    <header class="topbar">
        <div class="wrap {{ $navBawah ? 'ada-navbawah' : '' }}">
            <a class="brand" href="{{ route($menu[0][0]) }}">@include('layouts.logo', ['logoKelas' => '']) <span>FLEX <small class="brand-sub">Flexible Learning Experience</small></span></a>
            @foreach ($menu as [$rute, $label, $ikon, $aktif])
                <a href="{{ route($rute) }}" class="menu-atas {{ request()->routeIs(...$aktif) ? 'on' : '' }}">{{ $label }}@if ($rute === 'asisten.pesan' && $pesanBaru)<span class="menu-hitung" aria-label="{{ $pesanBaru }} pesan baru">{{ $pesanBaru }}</span>@endif</a>
            @endforeach
            @include('layouts.tema', ['temaKelas' => 'ikon', 'temaLabel' => null])
            <form method="post" action="{{ route('logout') }}">@csrf<button class="link-btn">Keluar</button></form>
        </div>
    </header>
    <main class="wrap">
        @include('layouts.flash')
        @yield('content')
    </main>
    @include('layouts.footer')
</div>
@if ($navBawah)
<nav class="navbawah" style="--n:{{ count($menu) }}">
    @foreach ($menu as [$rute, $label, $ikon, $aktif])
        <a href="{{ route($rute) }}" class="{{ request()->routeIs(...$aktif) ? 'on' : '' }}"><span class="ikon-hitung">{!! \App\Support\Ikon::svg($ikon) !!}@if ($rute === 'asisten.pesan' && $pesanBaru)<span class="menu-hitung">{{ $pesanBaru }}</span>@endif</span>{{ $label }}</a>
    @endforeach
</nav>
@endif
@endsection
