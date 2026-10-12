@extends('layouts.base')
@section('body')
<div class="page-col">
    <header class="topbar">
        <div class="wrap">
            <a class="brand" href="{{ route('asisten.dashboard') }}"><img src="/icons/icon-192.png" alt=""> <span>FLEX <small class="brand-sub">Flexible Learning Experience</small></span></a>
            <a href="{{ route('asisten.dashboard') }}" class="{{ request()->routeIs('asisten.dashboard', 'asisten.kelas.*') ? 'on' : '' }}">Kelas</a>
            <a href="{{ route('asisten.profil') }}" class="{{ request()->routeIs('asisten.profil') ? 'on' : '' }}">Profil</a>
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
@endsection
