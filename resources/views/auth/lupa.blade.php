@extends('layouts.base')
@section('title', 'Lupa Kata Sandi — FLEX')
@php($peran = old('peran', $peran))
@section('body')
<div class="auth">
    <div class="card">
        @include('layouts.logo', ['logoKelas' => 'logo'])
        <h1 class="center">Lupa kata sandi?</h1>
        <p class="center muted">Masukkan email akunmu. Kami kirim kode 6 angka untuk membuat kata sandi baru.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('lupa') }}">
            @csrf
            <div class="field">
                <label for="peran">Akun sebagai</label>
                <select id="peran" name="peran" required>
                    @foreach (['mahasiswa' => 'Mahasiswa', 'asisten' => 'Asisten dosen', 'dosen' => 'Dosen', 'admin' => 'Laboran'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($peran === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            @include('auth._captcha')
            <button class="btn block big">Kirim kode</button>
        </form>
        <p class="center small muted" style="margin-top:14px">Tidak bisa membuka email? Mahasiswa hubungi asisten kelas; asisten dan dosen hubungi laboran.</p>
        <p class="center small"><a href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
