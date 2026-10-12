@extends('layouts.base')
@section('title', 'Masuk Asisten — FLEX')
@section('body')
<div class="auth">
    <div class="card">
        <img class="logo" src="/icons/icon-192.png" alt="">
        <h1 class="center">Dashboard Asisten</h1>
        <p class="center muted">Kelola kelas, nyalakan QR, dan rekap absen.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('asisten.login') }}">
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
            <div class="field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya</label></div>
            <button class="btn block big">Masuk</button>
        </form>
        <p class="center small" style="margin-top:14px"><a href="{{ route('lupa') }}">Lupa kata sandi?</a> · <a href="{{ route('asisten.daftar') }}">Daftar akun asisten</a></p>
        <p class="center small muted">Mahasiswa? <a href="{{ route('login') }}">Masuk untuk absen</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
