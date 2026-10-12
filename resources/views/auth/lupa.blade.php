@extends('layouts.base')
@section('title', 'Lupa Kata Sandi')
@section('body')
<div class="auth">
    <div class="card">
        <h1>Lupa kata sandi?</h1>
        <div class="stack">
            <div>
                <h3>Mahasiswa</h3>
                <p class="muted">Hubungi asisten dosen kelasmu. Asisten bisa membuat kata sandi sementara dari menu <b>Mahasiswa</b> di kelas. Setelah masuk, segera ganti di menu <b>Akun</b>.</p>
            </div>
            <div>
                <h3>Asisten dosen</h3>
                <p class="muted">Hubungi admin sistem. Admin dapat mengganti kata sandi akun asisten dari menu <b>Profil &rsaquo; Kelola Akun Asisten</b>.</p>
            </div>
        </div>
        <div class="row" style="margin-top:12px">
            <a class="btn ghost" href="{{ route('login') }}">Login mahasiswa</a>
            <a class="btn ghost" href="{{ route('asisten.login') }}">Login asisten</a>
        </div>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
