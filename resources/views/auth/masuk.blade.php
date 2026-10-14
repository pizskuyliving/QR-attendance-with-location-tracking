@extends('layouts.base')
@section('title', 'Masuk — FLEX')
@php($peran = old('peran', $peran))
@section('body')
<div class="auth">
    <div class="card">
        @include('layouts.logo', ['logoKelas' => 'logo'])
        <h1 class="center" style="margin-bottom:0">FLEX</h1>
        <p class="center muted small" style="margin-bottom:14px">Flexible Learning Experience</p>
        @include('layouts.flash')
        @if (request('sesi') === 'hilang' && ! $errors->any())
            <div class="alert bad">Kata sandi benar, tetapi browser ini tidak menyimpan cookie login sehingga kamu terpental. Pastikan tidak memakai mode privat dan cookie tidak diblokir (iPhone: Pengaturan › Safari › matikan "Blokir Semua Cookie"), lalu masuk lagi.</div>
        @endif
        <form method="post" action="{{ route('masuk') }}">
            @csrf
            <div class="field">
                <label for="peran">Masuk sebagai</label>
                <select id="peran" name="peran" data-peran-pilih required>
                    @foreach (['mahasiswa' => 'Mahasiswa', 'asisten' => 'Asisten dosen', 'dosen' => 'Dosen', 'admin' => 'Laboran'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($peran === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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
        <p class="center" style="margin-top:14px" data-peran="mahasiswa" @if ($peran !== 'mahasiswa') hidden @endif>@if (config('absen.daftar_mahasiswa'))Belum punya akun? <a href="{{ route('daftar') }}"><b>Daftar di sini</b></a>@else<span class="small muted">Pendaftaran akun mahasiswa sedang ditutup laboran.</span>@endif</p>
        <p class="center" style="margin-top:14px" data-peran="asisten" @if ($peran !== 'asisten') hidden @endif>@if (config('absen.daftar_asisten'))Belum punya akun? <a href="{{ route('asisten.daftar') }}"><b>Daftar akun asisten</b></a>@else<span class="small muted">Pendaftaran akun asisten sedang ditutup laboran.</span>@endif</p>
        <p class="center small muted" style="margin-top:14px" data-peran="dosen admin" @if (! in_array($peran, ['dosen', 'admin'], true)) hidden @endif>Akun dosen dan laboran dibuat oleh laboran.</p>
        <p class="center small"><a href="{{ route('lupa', ['peran' => $peran]) }}" data-lupa>Lupa kata sandi?</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
@push('scripts')
<script>
document.querySelector('[data-peran-pilih]').addEventListener('change', e => {
    document.querySelectorAll('[data-peran]').forEach(el => el.hidden = !el.dataset.peran.split(' ').includes(e.target.value));
    const lupa = document.querySelector('[data-lupa]');
    lupa.href = lupa.href.replace(/peran=[a-z]+/, 'peran=' + e.target.value);
});
</script>
@endpush
