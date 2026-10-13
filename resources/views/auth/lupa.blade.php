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
                <h3>Asisten dosen &amp; dosen</h3>
                <p class="muted">Hubungi laboran. Laboran dapat mengganti kata sandi dari menu <b>Akun</b>.</p>
            </div>
        </div>
        <div class="row" style="margin-top:12px">
            <a class="btn ghost" href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a>
        </div>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
