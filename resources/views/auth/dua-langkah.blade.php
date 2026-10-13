@extends('layouts.base')
@section('title', 'Verifikasi Dua Langkah — FLEX')
@section('body')
<div class="auth">
    <div class="card">
        @include('layouts.logo', ['logoKelas' => 'logo'])
        <h1 class="center">Verifikasi dua langkah</h1>
        <p class="center muted">Buka aplikasi authenticator di HP-mu, lalu masukkan kode 6 angka untuk FLEX.</p>
        @include('layouts.flash')
        <form method="post" action="{{ route('asisten.login.2fa') }}">
            @csrf
            <div class="field">
                <label for="kode">Kode</label>
                <input id="kode" name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="9" required autofocus
                       style="font-size:1.6rem;letter-spacing:6px;text-align:center" placeholder="000000">
            </div>
            <button class="btn block big">Verifikasi</button>
        </form>
        <details style="margin-top:14px" class="small">
            <summary>HP hilang atau tidak bisa membuka aplikasi?</summary>
            <p class="muted">Masukkan salah satu <b>kode cadangan</b> (bentuk XXXX-XXXX) di kolom yang sama. Setiap kode cadangan hanya bisa dipakai sekali. Jika kode cadangan juga hilang, minta laboran mereset verifikasi dua langkah akunmu.</p>
        </details>
        <p class="center small" style="margin-top:14px"><a href="{{ route('asisten.login') }}">&larr; Kembali ke halaman masuk</a></p>
    </div>
    @include('layouts.footer', ['ringkas' => true])
</div>
<script>
    // izinkan huruf untuk kode cadangan, tapi ubah tampilan papan ketik ke angka dulu
    document.getElementById('kode').addEventListener('input', e => { if (/[a-z]/i.test(e.target.value)) e.target.setAttribute('inputmode', 'text'); });
</script>
@endsection
