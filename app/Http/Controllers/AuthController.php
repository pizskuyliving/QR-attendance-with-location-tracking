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

        return view('auth.masuk', ['peran' => 'mahasiswa']);
    }

    /** Satu halaman masuk untuk semua peran; pilihan "Masuk sebagai" menentukan jenis akun yang dicek. */
    public function masuk(Request $request)
    {
        $peran = $request->validate(['peran' => 'required|in:mahasiswa,asisten,dosen,admin'])['peran'];

        return $peran === 'mahasiswa' ? $this->loginMahasiswa($request) : $this->loginAsisten($request);
    }

    /** Kata sandi cocok untuk jenis akun lain: beri tahu peran yang benar, bukan sekadar "salah". */
    private function salahPeran(Request $request, bool $diMahasiswa)
    {
        $kredensial = ['email' => mb_strtolower(trim($request->input('email'))), 'password' => $request->input('password')];
        $cocok = $diMahasiswa ? Auth::guard('web')->validate($kredensial) : Auth::guard('mahasiswa')->validate($kredensial);
        $pesan = ! $cocok ? 'Email atau kata sandi salah.'
            : ($diMahasiswa ? 'Akun ini bukan akun mahasiswa. Pilih peran Asisten, Dosen, atau Laboran.' : 'Ini akun mahasiswa. Pilih "Masuk sebagai: Mahasiswa".');

        return back()->withErrors(['email' => $pesan])->onlyInput('email', 'peran');
    }

    private function loginMahasiswa(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->onlyInput('email', 'peran');
        }

        $email = mb_strtolower(trim($data['email']));
        if (! Auth::guard('mahasiswa')->validate(['email' => $email, 'password' => $data['password']])) {
            return $this->salahPeran($request, true);
        }

        $mhs = Mahasiswa::where('email', $email)->first();
        if (! $mhs->terverifikasi()) {
            // sudah daftar tapi belum memasukkan kode -> lanjutkan verifikasi
            $request->session()->put('verifikasi_mhs', $mhs->id);

            return redirect()->route('daftar.verifikasi')->withErrors(['kode' => 'Akun belum diverifikasi. Masukkan kode yang dikirim ke email.']);
        }

        Auth::guard('mahasiswa')->login($mhs, $request->boolean('ingat'));
        $request->session()->regenerate();

        return $this->keTujuan($request, route('mhs.home'), $mhs);
    }

    /**
     * Setelah login berhasil, lewat /masuk/cek dulu: bila browser tidak menyimpan cookie sesi
     * (mode privat, cookie diblokir, data situs baru dihapus), pengguna mendapat pesan jelas, bukan terpental diam-diam.
     */
    private function keTujuan(Request $request, string $bawaan, Mahasiswa|User $akun)
    {
        $tujuan = $request->session()->pull('url.intended', $bawaan);
        $path = parse_url($tujuan, PHP_URL_PATH) ?: '/';
        $query = parse_url($tujuan, PHP_URL_QUERY);
        $ke = $path.($query ? '?'.$query : '');
        // halaman yang dibuka sebelum login (mis. tautan asisten lalu masuk sebagai mahasiswa) belum tentu boleh dibuka akun ini
        if (! self::bolehDibuka($path, $akun)) {
            $ke = parse_url($bawaan, PHP_URL_PATH) ?: '/';
        }

        return redirect()->route('masuk.cek', ['ke' => $ke]);
    }

    /** Apakah path ini memang halaman untuk akun/peran tersebut (area mahasiswa vs staf, middleware peran). */
    private static function bolehDibuka(string $path, Mahasiswa|User $akun): bool
    {
        if ($akun instanceof Mahasiswa) {
            return $path === '/m' || str_starts_with($path, '/m/');
        }
        if ($path !== '/asisten' && ! str_starts_with($path, '/asisten/')) {
            return false;
        }
        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (\Throwable) {
            return false;
        }
        foreach ($route->gatherMiddleware() as $m) {
            if (is_string($m) && str_starts_with($m, 'peran:') && ! in_array($akun->role, explode(',', substr($m, 6)), true)) {
                return false;
            }
        }

        return true;
    }

    public function cekSesi(Request $request)
    {
        $ke = (string) $request->query('ke', '/');
        if (! str_starts_with($ke, '/') || str_starts_with($ke, '//') || str_contains($ke, '\\')) {
            $ke = '/';
        }
        if (Auth::guard('mahasiswa')->check() || Auth::guard('web')->check()) {
            return redirect($ke);
        }

        return redirect()->route(str_starts_with($ke, '/asisten') ? 'asisten.login' : 'login', ['sesi' => 'hilang']);
    }

    public function formAsisten()
    {
        if (Auth::check()) {
            return redirect()->route('asisten.dashboard');
        }

        // halaman masuk selalu dibuka di peran Mahasiswa; Asisten hanya dipilihkan saat kembali membawa pesan galat
        return view('auth.masuk', ['peran' => session('errors') ? 'asisten' : 'mahasiswa']);
    }

    private function loginAsisten(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->onlyInput('email', 'peran');
        }

        $data['email'] = mb_strtolower(trim($data['email']));
        if (! Auth::validate($data)) {
            return $this->salahPeran($request, false);
        }
        $user = User::where('email', $data['email'])->first();
        if ($user->status === 'menunggu') {
            return back()->withErrors(['email' => 'Akunmu masih menunggu persetujuan laboran.'])->onlyInput('email', 'peran');
        }
        if ($user->status !== 'aktif') {
            return back()->withErrors(['email' => 'Pendaftaran akun ini ditolak laboran. Hubungi laboran bila ini keliru.'])->onlyInput('email', 'peran');
        }

        if ($user->pakaiDuaLangkah()) {
            // kata sandi benar, tapi belum login: minta kode authenticator dulu
            $request->session()->regenerate();
            $request->session()->put('login_2fa', ['id' => $user->id, 'ingat' => $request->boolean('ingat'), 'waktu' => time(), 'salah' => 0]);

            return redirect()->route('asisten.login.2fa');
        }

        Auth::login($user, $request->boolean('ingat'));
        $request->session()->regenerate();

        return $this->keTujuan($request, route('asisten.dashboard'), $user);
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

        return $this->keTujuan($request, route('asisten.dashboard'), $user);
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
        Auth::guard('mahasiswa')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Ganti kata sandi untuk akun yang sedang login (mahasiswa atau asisten). */
    public function gantiPassword(Request $request)
    {
        // pakai guard sesuai halaman asal, supaya browser yang login sebagai keduanya tidak salah akun
        $guard = $request->routeIs('mhs.*') ? 'mahasiswa' : 'web';
        /** @var \App\Models\User|\App\Models\Mahasiswa $user */
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
