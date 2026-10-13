@extends('layouts.base')
@section('title', 'Daftar Akun Mahasiswa — FLEX')
@section('body')
<div class="auth">
    <div class="card" style="max-width:460px">
        @include('layouts.logo', ['logoKelas' => 'logo'])
        <h1 class="center">Daftar Akun Mahasiswa</h1>
        <p class="center muted">Isi data sesuai KTM. Kode verifikasi akan dikirim ke email kamu.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('daftar') }}" novalidate>
            @csrf
            <div class="field">
                <label for="npm">NPM</label>
                <input id="npm" name="npm" value="{{ old('npm') }}" inputmode="numeric" pattern="[0-9]*" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="kelas">Kelas</label>
                <select id="kelas" name="kelas" required>
                    <option value="">Pilih kelas…</option>
                    @foreach ($daftarKelas as $k)<option value="{{ $k }}" @selected(old('kelas') === $k)>Kelas {{ $k }}</option>@endforeach
                </select>
                <div class="small muted" style="margin-top:4px">Mata kuliah dipilih nanti di akunmu setelah daftar.</div>
            </div>
            <div class="field">
                <label for="nama">Nama lengkap <span class="muted">(sesuai KTM)</span></label>
                <input id="nama" name="nama" value="{{ old('nama') }}" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                    @if ($domain = config('absen.domain_email')) placeholder="nama{{ '@'.$domain[0] }}" @endif>
                <div class="small muted" style="margin-top:4px">@if (config('absen.domain_email'))Wajib email kampus ({{ \App\Http\Controllers\RegisterController::teksDomain() }}). @endif Kode verifikasi akan dikirim ke email ini.</div>
            </div>
            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="small muted" style="margin-top:4px">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            @include('auth._captcha')
            <button class="btn block big" @disabled(! count($daftarKelas))>Daftar</button>
        </form>
        <p class="center" style="margin-top:14px">Sudah punya akun? <a href="{{ route('login') }}"><b>Masuk</b></a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
