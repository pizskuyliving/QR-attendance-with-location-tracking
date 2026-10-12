@extends('layouts.base')
@section('title', 'Verifikasi Email — FLEX')
@section('body')
<div class="auth">
    <div class="card">
        <h1 class="center">Cek email kamu</h1>
        <p class="center muted">Kami mengirim kode 6 angka ke <b>{{ $email }}</b>. Kode berlaku 10 menit. Cek juga folder Spam.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('daftar.verifikasi') }}">
            @csrf
            <div class="field">
                <label for="kode">Kode verifikasi</label>
                <input id="kode" name="kode" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus
                    style="font-size:1.6rem;letter-spacing:10px;text-align:center">
            </div>
            <button class="btn block big">Verifikasi</button>
        </form>
        <form method="post" action="{{ route('daftar.ulang') }}" class="center" style="margin-top:14px">
            @csrf<button class="link-btn">Tidak menerima kode? Kirim ulang</button>
        </form>
        <p class="center small muted">Salah email? <a href="{{ route('daftar') }}">Daftar ulang</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
