@extends('layouts.base')
@section('title', 'Daftar Akun Asisten — FLEX')
@section('body')
<div class="auth">
    <div class="card" style="max-width:460px">
        @include('layouts.logo', ['logoKelas' => 'logo'])
        <h1 class="center">Daftar Akun Asisten</h1>
        <p class="center muted">Akun baru bisa dipakai setelah <b>disetujui laboran</b>.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('asisten.daftar') }}" novalidate>
            @csrf
            <div class="field">
                <label for="name">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="keterangan">Mata kuliah yang diampu</label>
                <input id="keterangan" name="keterangan" value="{{ old('keterangan') }}" placeholder="contoh: Basis Data kelas A dan B" required>
                <div class="small muted" style="margin-top:4px">Membantu laboran memastikan kamu memang asisten.</div>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="small muted" style="margin-top:4px">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            @include('auth._captcha')
            <button class="btn block big">Kirim pendaftaran</button>
        </form>
        <p class="center" style="margin-top:14px">Sudah punya akun? <a href="{{ route('asisten.login') }}"><b>Masuk</b></a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
