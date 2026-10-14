<?php

namespace App\Http\Controllers;

use App\Mail\KodeVerifikasi;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Lupa kata sandi: isi email -> kode 6 angka dikirim ke email -> isi kode + kata sandi baru.
 * Sama dengan alur daftar mahasiswa. Kode disimpan ter-hash di password_reset_tokens dengan kunci
 * "m:email" (mahasiswa) atau "u:email" (asisten/dosen/laboran), karena email bisa sama di dua tabel.
 */
class LupaSandiController extends Controller
{
    private const MENIT_BERLAKU = 15;

    private const MAKS_PERCOBAAN = 5;

    /** Jeda minimal antar pengiriman kode ke email yang sama. */
    private const DETIK_JEDA = 60;

    public function form(Request $request)
    {
        $peran = in_array($request->query('peran'), ['mahasiswa', 'asisten', 'dosen', 'admin'], true) ? $request->query('peran') : 'mahasiswa';

        return view('auth.lupa', ['peran' => $peran]);
    }

    public function kirim(Request $request)
    {
        $data = $request->validate([
            'peran' => 'required|in:mahasiswa,asisten,dosen,admin',
            'email' => 'required|email|max:100',
        ]);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->onlyInput('email', 'peran');
        }

        $jenis = $data['peran'] === 'mahasiswa' ? 'm' : 'u';
        $email = mb_strtolower(trim($data['email']));
        $this->kirimKode($jenis, $email);
        $request->session()->put('reset_sandi', ['jenis' => $jenis, 'email' => $email]);

        // pesan selalu sama, terdaftar atau tidak, agar halaman ini tidak bisa dipakai menebak email yang terdaftar
        return redirect()->route('lupa.kode')->with('ok', 'Jika email tersebut terdaftar, kode 6 angka sudah dikirim. Cek juga folder Spam.');
    }

    public function formKode(Request $request)
    {
        $reset = $request->session()->get('reset_sandi');
        if (! $reset) {
            return redirect()->route('lupa');
        }

        return view('auth.lupa-kode', ['email' => self::samarkan($reset['email'])]);
    }

    public function simpan(Request $request)
    {
        $reset = $request->session()->get('reset_sandi');
        if (! $reset) {
            return redirect()->route('lupa')->withErrors(['email' => 'Sesi habis. Masukkan email lagi.']);
        }
        $request->validate([
            'kode' => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['kode.digits' => 'Kode terdiri dari 6 angka.'], ['password' => 'kata sandi baru']);

        $kunci = $reset['jenis'].':'.$reset['email'];
        $batas = 'lupa-sandi:'.$kunci;
        if (RateLimiter::tooManyAttempts($batas, self::MAKS_PERCOBAAN)) {
            return back()->withErrors(['kode' => 'Terlalu banyak kode salah. Tekan "Kirim ulang kode" untuk kode baru.']);
        }

        $baris = DB::table('password_reset_tokens')->where('email', $kunci)->first();
        $akun = $this->akun($reset['jenis'], $reset['email']);
        $kedaluwarsa = $baris && now()->subMinutes(self::MENIT_BERLAKU)->gt($baris->created_at);
        if (! $baris || ! $akun || $kedaluwarsa) {
            return back()->withErrors(['kode' => 'Kode sudah kedaluwarsa atau tidak berlaku. Tekan "Kirim ulang kode".']);
        }
        if (! Hash::check($request->kode, $baris->token)) {
            RateLimiter::hit($batas, self::MENIT_BERLAKU * 60);

            return back()->withErrors(['kode' => 'Kode salah. Sisa percobaan: '.RateLimiter::remaining($batas, self::MAKS_PERCOBAAN).'.']);
        }

        $akun->password = $request->password;
        $akun->setRememberToken(Str::random(60)); // "ingat saya" di perangkat lain tidak berlaku lagi
        $akun->save();
        DB::table('password_reset_tokens')->where('email', $kunci)->delete();
        RateLimiter::clear($batas);
        $request->session()->forget('reset_sandi');

        $siapa = $akun instanceof Mahasiswa ? "{$akun->npm} {$akun->nama}" : "{$akun->name} ({$akun->email})";
        LogAktivitas::catat(null, 'lupa_sandi', "{$siapa} mengatur ulang kata sandi lewat email");

        return redirect()->route($akun instanceof Mahasiswa ? 'login' : 'asisten.login')
            ->with('ok', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
    }

    public function kirimUlang(Request $request)
    {
        $reset = $request->session()->get('reset_sandi');
        if (! $reset) {
            return redirect()->route('lupa');
        }
        $this->kirimKode($reset['jenis'], $reset['email']);

        return back()->with('ok', 'Jika email tersebut terdaftar, kode baru sudah dikirim.');
    }

    /** Hanya akun yang memang bisa masuk: mahasiswa yang sudah verifikasi email, atau staf berstatus aktif. */
    private function akun(string $jenis, string $email): Mahasiswa|User|null
    {
        return $jenis === 'm'
            ? Mahasiswa::where('email', $email)->whereNotNull('email_verified_at')->first()
            : User::where('email', $email)->where('status', 'aktif')->first();
    }

    private function kirimKode(string $jenis, string $email): void
    {
        $akun = $this->akun($jenis, $email);
        if (! $akun) {
            return;
        }
        $kunci = $jenis.':'.$email;
        $lama = DB::table('password_reset_tokens')->where('email', $kunci)->value('created_at');
        if ($lama && now()->subSeconds(self::DETIK_JEDA)->lt($lama)) {
            return; // baru saja dikirim; cegah email bertubi-tubi
        }

        $kode = (string) random_int(100000, 999999);
        DB::table('password_reset_tokens')->updateOrInsert(['email' => $kunci], ['token' => Hash::make($kode), 'created_at' => now()]);
        RateLimiter::clear('lupa-sandi:'.$kunci);
        try {
            Mail::to($email)->send(new KodeVerifikasi($akun instanceof Mahasiswa ? $akun->nama : $akun->name, $kode, self::MENIT_BERLAKU, 'sandi'));
        } catch (\Throwable $e) {
            Log::error('Gagal kirim kode lupa sandi', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    private static function samarkan(string $email): string
    {
        [$u, $d] = explode('@', $email, 2) + [1 => ''];

        return mb_substr($u, 0, 2).str_repeat('•', max(1, mb_strlen($u) - 2)).'@'.$d;
    }
}
