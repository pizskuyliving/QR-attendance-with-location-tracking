@extends('layouts.base')
@section('title', 'Kata Sandi Baru — FLEX')
@section('body')
<div class="auth">
    <div class="card">
        <h1 class="center">Buat kata sandi baru</h1>
        <p class="center muted">Masukkan kode 6 angka yang dikirim ke <b>{{ $email }}</b>. Kode berlaku 15 menit.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('lupa.kode') }}">
            @csrf
            <div class="field">
                <label for="kode">Kode dari email</label>
                <input id="kode" name="kode" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus
                    style="font-size:1.6rem;letter-spacing:10px;text-align:center">
            </div>
            <div class="field">
                <label for="password">Kata sandi baru</label>
                <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                <div class="small muted" style="margin-top:4px">Minimal 8 karakter, berisi huruf dan angka.</div>
            </div>
            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            </div>
            <button class="btn block big">Simpan kata sandi</button>
        </form>
        <form method="post" action="{{ route('lupa.ulang') }}" class="center" style="margin-top:14px">
            @csrf<button class="link-btn">Tidak menerima kode? Kirim ulang</button>
        </form>
        <p class="center small muted">Salah email? <a href="{{ route('lupa') }}">Ulangi dari awal</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
@endsection
