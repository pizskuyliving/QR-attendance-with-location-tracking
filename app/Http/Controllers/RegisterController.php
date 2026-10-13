<?php

namespace App\Http\Controllers;

use App\Mail\KodeVerifikasi;
use App\Models\Mahasiswa;
use App\Support\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Pendaftaran akun mahasiswa: isi form -> kode 6 digit dikirim ke email -> verifikasi -> akun aktif.
 * Jika NPM sudah diimpor asisten tapi belum pernah daftar, akun tersebut "diklaim" (data diperbarui).
 */
class RegisterController extends Controller
{
    private const MENIT_BERLAKU = 10;

    private const MAKS_PERCOBAAN = 5;

    /** Pendaftaran ditutup laboran dari menu Sistem. */
    public static function ditutup(string $siapa = 'mahasiswa')
    {
        return redirect()->route($siapa === 'asisten' ? 'asisten.login' : 'login')->withErrors(['email' => "Pendaftaran akun {$siapa} sedang ditutup. Hubungi laboran."]);
    }

    public function form()
    {
        if (Auth::guard('mahasiswa')->check()) {
            return redirect()->route('mhs.home');
        }
        if (! config('absen.daftar_mahasiswa')) {
            return self::ditutup();
        }

        return view('auth.daftar', ['daftarKelas' => config('absen.kelas')]);
    }

    public function store(Request $request)
    {
        if (! config('absen.daftar_mahasiswa')) {
            return self::ditutup();
        }
        $data = $request->validate([
            'npm' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'kelas' => ['required', Rule::in(config('absen.kelas'))],
            'nama' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s.\'-]+$/u'],
            'email' => ['required', 'email:rfc', 'max:100', function ($attr, $nilai, $gagal) {
                if (! self::domainKampus($nilai)) {
                    $gagal('Gunakan email kampus ('.self::teksDomain().'). Email pribadi seperti Gmail tidak bisa dipakai.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'npm.regex' => 'NPM hanya boleh berisi angka (6–20 digit).',
            'kelas.in' => 'Pilih kelas dari daftar.',
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, petik, dan tanda hubung.',
        ], [
            'npm' => 'NPM', 'nama' => 'nama lengkap', 'password' => 'kata sandi',
        ]);

        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
        }

        $email = mb_strtolower(trim($data['email']));
        $mhs = Mahasiswa::where('npm', $data['npm'])->first();

        if ($mhs && $mhs->terverifikasi()) {
            return back()->withErrors(['npm' => 'NPM ini sudah terdaftar. Silakan masuk, atau hubungi asisten jika lupa kata sandi.'])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
        }
        // pendaftaran orang lain yang kodenya masih berlaku tidak boleh diambil alih
        $sedangProses = fn (Mahasiswa $m) => $m->email && $m->email !== $email && $m->kode_kedaluwarsa?->isFuture();
        if ($mhs && $sedangProses($mhs)) {
            return back()->withErrors(['npm' => 'NPM ini sedang dalam proses pendaftaran. Coba lagi beberapa menit lagi, atau hubungi asisten.'])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
        }
        $emailDipakai = Mahasiswa::where('email', $email)->when($mhs, fn ($q) => $q->whereKeyNot($mhs->id))->first();
        if ($emailDipakai) {
            if ($emailDipakai->terverifikasi()) {
                return back()->withErrors(['email' => 'Email ini sudah dipakai akun lain.'])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
            }
            if ($emailDipakai->kode_kedaluwarsa?->isFuture()) {
                return back()->withErrors(['email' => 'Email ini sedang dipakai untuk pendaftaran lain. Coba lagi beberapa menit lagi.'])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
            }
            $emailDipakai->update(['email' => null]); // pendaftaran lama yang tidak diselesaikan
        }

        $isian = [
            'nama' => preg_replace('/\s+/', ' ', trim($data['nama'])),
            'kelas' => $data['kelas'],
            'email' => $email,
            'password' => $data['password'],
        ];
        $mhs = $mhs ? tap($mhs)->update($isian) : Mahasiswa::create($isian + ['npm' => $data['npm']]);

        if (! $this->kirimKode($mhs)) {
            return back()->withErrors(['email' => 'Email verifikasi gagal dikirim. Periksa alamat email atau coba lagi nanti.'])->withInput($request->only('npm', 'kelas', 'nama', 'email'));
        }
        $request->session()->put('verifikasi_mhs', $mhs->id);

        return redirect()->route('daftar.verifikasi');
    }

    public function formVerifikasi(Request $request)
    {
        $mhs = Mahasiswa::find($request->session()->get('verifikasi_mhs'));
        if (! $mhs || $mhs->terverifikasi()) {
            return redirect()->route('login');
        }

        return view('auth.verifikasi', ['email' => self::samarkan($mhs->email)]);
    }

    public function verifikasi(Request $request)
    {
        $request->validate(['kode' => 'required|digits:6'], ['kode.digits' => 'Kode terdiri dari 6 angka.']);
        $mhs = Mahasiswa::find($request->session()->get('verifikasi_mhs'));
        if (! $mhs || $mhs->terverifikasi()) {
            return redirect()->route('login');
        }

        if (! $mhs->kode_verifikasi || $mhs->kode_kedaluwarsa?->isPast()) {
            return back()->withErrors(['kode' => 'Kode sudah kedaluwarsa. Tekan "Kirim ulang kode".']);
        }
        if ($mhs->percobaan_kode >= self::MAKS_PERCOBAAN) {
            return back()->withErrors(['kode' => 'Terlalu banyak percobaan salah. Tekan "Kirim ulang kode" untuk kode baru.']);
        }
        if (! Hash::check($request->kode, $mhs->kode_verifikasi)) {
            $mhs->increment('percobaan_kode');

            return back()->withErrors(['kode' => 'Kode salah. Sisa percobaan: '.(self::MAKS_PERCOBAAN - $mhs->percobaan_kode).'.']);
        }

        $mhs->update(['email_verified_at' => now(), 'kode_verifikasi' => null, 'kode_kedaluwarsa' => null, 'percobaan_kode' => 0]);
        $request->session()->forget('verifikasi_mhs');
        Auth::guard('mahasiswa')->login($mhs);
        $request->session()->regenerate();

        // mata kuliah dipilih sendiri oleh mahasiswa setelah akun aktif
        return redirect()->route($mhs->kelas()->exists() ? 'mhs.home' : 'mhs.kelas')->with('ok', 'Akun berhasil diaktifkan. Selamat datang!');
    }

    public function kirimUlang(Request $request)
    {
        $mhs = Mahasiswa::find($request->session()->get('verifikasi_mhs'));
        if (! $mhs || $mhs->terverifikasi()) {
            return redirect()->route('login');
        }

        return $this->kirimKode($mhs)
            ? back()->with('ok', 'Kode baru sudah dikirim ke email kamu.')
            : back()->withErrors(['kode' => 'Email gagal dikirim. Coba lagi nanti.']);
    }

    private function kirimKode(Mahasiswa $mhs): bool
    {
        $kode = (string) random_int(100000, 999999);
        $mhs->update([
            'kode_verifikasi' => Hash::make($kode),
            'kode_kedaluwarsa' => now()->addMinutes(self::MENIT_BERLAKU),
            'percobaan_kode' => 0,
        ]);
        try {
            Mail::to($mhs->email)->send(new KodeVerifikasi($mhs->nama, $kode, self::MENIT_BERLAKU));

            return true;
        } catch (\Throwable $e) {
            Log::error('Gagal kirim kode verifikasi', ['npm' => $mhs->npm, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** Email harus berdomain kampus, atau subdomainnya (mis. npm@student.uir.ac.id). */
    public static function domainKampus(string $email): bool
    {
        $daftar = config('absen.domain_email');
        if (! $daftar) {
            return true;
        }
        $domain = strtolower(substr(strrchr(trim($email), '@') ?: '', 1));
        foreach ($daftar as $d) {
            if ($domain === $d || str_ends_with($domain, '.'.$d)) {
                return true;
            }
        }

        return false;
    }

    public static function teksDomain(): string
    {
        return implode(' / ', array_map(fn ($d) => '@'.$d, config('absen.domain_email')));
    }

    private static function samarkan(string $email): string
    {
        [$u, $d] = explode('@', $email, 2);

        return mb_substr($u, 0, 2).str_repeat('•', max(1, mb_strlen($u) - 2)).'@'.$d;
    }
}
