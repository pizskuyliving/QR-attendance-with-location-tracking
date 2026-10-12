<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function formMahasiswa()
    {
        if (Auth::guard('mahasiswa')->check()) {
            return redirect()->route('mhs.home');
        }

        return view('auth.mahasiswa');
    }

    public function loginMahasiswa(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->onlyInput('email');
        }

        $email = mb_strtolower(trim($data['email']));
        if (! Auth::guard('mahasiswa')->validate(['email' => $email, 'password' => $data['password']])) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $mhs = Mahasiswa::where('email', $email)->first();
        if (! $mhs->terverifikasi()) {
            // sudah daftar tapi belum memasukkan kode -> lanjutkan verifikasi
            $request->session()->put('verifikasi_mhs', $mhs->id);

            return redirect()->route('daftar.verifikasi')->withErrors(['kode' => 'Akun belum diverifikasi. Masukkan kode yang dikirim ke email.']);
        }

        Auth::guard('mahasiswa')->login($mhs, $request->boolean('ingat'));
        $request->session()->regenerate();

        return redirect()->intended(route('mhs.home'));
    }

    public function formAsisten()
    {
        if (Auth::check()) {
            return redirect()->route('asisten.dashboard');
        }

        return view('auth.asisten');
    }

    public function loginAsisten(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->onlyInput('email');
        }

        if (! Auth::validate($data)) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $user = User::where('email', $data['email'])->first();
        if ($user->status === 'menunggu') {
            return back()->withErrors(['email' => 'Akunmu masih menunggu persetujuan admin.'])->onlyInput('email');
        }
        if ($user->status !== 'aktif') {
            return back()->withErrors(['email' => 'Pendaftaran akun ini ditolak admin. Hubungi admin bila ini keliru.'])->onlyInput('email');
        }

        if ($user->pakaiDuaLangkah()) {
            // kata sandi benar, tapi belum login: minta kode authenticator dulu
            $request->session()->regenerate();
            $request->session()->put('login_2fa', ['id' => $user->id, 'ingat' => $request->boolean('ingat'), 'waktu' => time(), 'salah' => 0]);

            return redirect()->route('asisten.login.2fa');
        }

        Auth::login($user, $request->boolean('ingat'));
        $request->session()->regenerate();

        return redirect()->intended(route('asisten.dashboard'));
    }

    private const MENIT_2FA = 5;

    private const MAKS_SALAH_2FA = 5;

    public function formDuaLangkah(Request $request)
    {
        if (! $this->calonDuaLangkah($request)) {
            return redirect()->route('asisten.login')->withErrors(['email' => 'Sesi masuk habis. Masukkan email dan kata sandi lagi.']);
        }

        return view('auth.dua-langkah');
    }

    public function duaLangkah(Request $request)
    {
        $request->validate(['kode' => 'required|string|max:20'], [], ['kode' => 'kode']);
        $user = $this->calonDuaLangkah($request);
        if (! $user) {
            return redirect()->route('asisten.login')->withErrors(['email' => 'Sesi masuk habis. Masukkan email dan kata sandi lagi.']);
        }

        if (! $user->cekKodeDuaLangkah($request->kode)) {
            $calon = $request->session()->get('login_2fa');
            $calon['salah']++;
            if ($calon['salah'] >= self::MAKS_SALAH_2FA) {
                $request->session()->forget('login_2fa');

                return redirect()->route('asisten.login')->withErrors(['email' => 'Terlalu banyak kode salah. Masuk ulang dari awal.']);
            }
            $request->session()->put('login_2fa', $calon);

            return back()->withErrors(['kode' => 'Kode salah. Sisa percobaan: '.(self::MAKS_SALAH_2FA - $calon['salah']).'.']);
        }

        $ingat = $request->session()->pull('login_2fa')['ingat'];
        Auth::login($user, $ingat);
        $request->session()->regenerate();

        return redirect()->intended(route('asisten.dashboard'));
    }

    private function calonDuaLangkah(Request $request): ?User
    {
        $calon = $request->session()->get('login_2fa');
        if (! $calon || time() - $calon['waktu'] > self::MENIT_2FA * 60) {
            return null;
        }
        $user = User::find($calon['id']);

        return $user?->pakaiDuaLangkah() ? $user : null;
    }

    public function logout(Request $request)
    {
        $keMahasiswa = Auth::guard('mahasiswa')->check();
        Auth::guard('mahasiswa')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($keMahasiswa ? 'login' : 'asisten.login');
    }

    public function lupa()
    {
        return view('auth.lupa');
    }

    /** Ganti kata sandi untuk akun yang sedang login (mahasiswa atau asisten). */
    public function gantiPassword(Request $request)
    {
        // pakai guard sesuai halaman asal, supaya browser yang login sebagai keduanya tidak salah akun
        $guard = $request->routeIs('mhs.*') ? 'mahasiswa' : 'web';
        $user = Auth::guard($guard)->user();
        $request->validate([
            'password_lama' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        if (! Hash::check($request->password_lama, $user->password)) {
            return back()->withErrors(['password_lama' => 'Kata sandi lama salah.']);
        }
        $user->password = $request->password;
        $user->setRememberToken(Str::random(60)); // "ingat saya" di perangkat lain tidak berlaku lagi
        $user->save();

        return back()->with('ok', 'Kata sandi berhasil diganti.');
    }
}
