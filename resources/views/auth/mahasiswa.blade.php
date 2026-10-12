@extends('layouts.base')
@section('title', 'Masuk Mahasiswa — FLEX')
@section('body')
<div class="auth">
    <div class="card">
        <img class="logo" src="/icons/icon-192.png" alt="">
        <h1 class="center" style="margin-bottom:0">FLEX</h1>
        <p class="center muted small" style="margin-bottom:14px">Flexible Learning Experience</p>
        <p class="center muted">Masuk dengan email yang kamu daftarkan.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('login.mahasiswa') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            @include('auth._captcha')
            <div class="field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label></div>
            <button class="btn block big">Masuk</button>
        </form>
        <p class="center" style="margin-top:14px">Belum punya akun? <a href="{{ route('daftar') }}"><b>Daftar di sini</b></a></p>
        <p class="center small"><a href="{{ route('lupa') }}">Lupa kata sandi?</a></p>
        <p class="center small muted">Asisten dosen? <a href="{{ route('asisten.login') }}">Masuk ke dashboard</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
