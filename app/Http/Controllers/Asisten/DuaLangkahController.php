<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Verifikasi dua langkah (2FA) untuk akun asisten & admin memakai aplikasi authenticator. */
class DuaLangkahController extends Controller
{
    public const JUMLAH_CADANGAN = 8;

    /** Langkah 1: buat rahasia calon (belum aktif) lalu tampilkan QR di halaman profil. */
    public function siapkan(Request $request)
    {
        abort_if($request->user()->pakaiDuaLangkah(), 422, '2FA sudah aktif.');
        $request->session()->put('dua_langkah_calon', Totp::rahasiaBaru());

        return redirect()->route('asisten.profil', ['#dua-langkah']);
    }

    /** Langkah 2: kode pertama dari aplikasi membuktikan QR sudah dipindai dengan benar. */
    public function konfirmasi(Request $request)
    {
        $request->validate(['kode' => 'required|string']);
        $calon = $request->session()->get('dua_langkah_calon');
        if (! $calon) {
            return redirect()->route('asisten.profil')->withErrors(['kode' => 'Sesi pemasangan habis. Mulai lagi.']);
        }
        $langkah = Totp::cocok($calon, $request->kode);
        if ($langkah === null) {
            return back()->withErrors(['kode' => 'Kode salah. Pastikan jam HP otomatis, lalu masukkan kode yang sedang tampil.']);
        }

        $u = $request->user();
        $cadangan = $this->buatCadangan();
        $u->forceFill([
            'dua_langkah_rahasia' => $calon,
            'dua_langkah_aktif_at' => now(),
            'dua_langkah_terakhir' => $langkah,
            'dua_langkah_cadangan' => array_map(fn ($k) => Hash::make(str_replace('-', '', $k)), $cadangan),
        ])->save();
        $request->session()->forget('dua_langkah_calon');
        LogAktivitas::catat(null, '2fa_aktif', "{$u->name} ({$u->email}) mengaktifkan verifikasi dua langkah");

        return redirect()->route('asisten.profil', ['#dua-langkah'])
            ->with('ok', 'Verifikasi dua langkah aktif. Simpan kode cadangan di bawah ini.')
            ->with('kode_cadangan', $cadangan);
    }

    public function batalSiapkan(Request $request)
    {
        $request->session()->forget('dua_langkah_calon');

        return redirect()->route('asisten.profil');
    }

    public function cadanganBaru(Request $request)
    {
        $u = $request->user();
        $request->validate(['password' => 'required']);
        if (! Hash::check($request->password, $u->password)) {
            return back()->withErrors(['password_2fa' => 'Kata sandi salah.']);
        }
        $cadangan = $this->buatCadangan();
        $u->forceFill(['dua_langkah_cadangan' => array_map(fn ($k) => Hash::make(str_replace('-', '', $k)), $cadangan)])->save();

        return redirect()->route('asisten.profil', ['#dua-langkah'])
            ->with('ok', 'Kode cadangan baru dibuat. Kode lama tidak berlaku lagi.')
            ->with('kode_cadangan', $cadangan);
    }

    public function matikan(Request $request)
    {
        $u = $request->user();
        $request->validate(['password' => 'required', 'kode' => 'required|string']);
        if (! Hash::check($request->password, $u->password) || ! $u->cekKodeDuaLangkah($request->kode)) {
            return back()->withErrors(['password_2fa' => 'Kata sandi atau kode salah.']);
        }
        $this->kosongkan($u);
        LogAktivitas::catat(null, '2fa_mati', "{$u->name} ({$u->email}) mematikan verifikasi dua langkah");

        return redirect()->route('asisten.profil')->with('ok', 'Verifikasi dua langkah dimatikan.');
    }

    /** Admin: reset 2FA asisten yang kehilangan HP dan kode cadangannya. */
    public function resetOlehAdmin(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $this->kosongkan($user);
        LogAktivitas::catat(null, '2fa_reset', "{$request->user()->name} mereset verifikasi dua langkah milik {$user->name} ({$user->email})");

        return back()->with('ok', "Verifikasi dua langkah {$user->name} direset. Minta dia memasang ulang dari halaman Profil.");
    }

    private function kosongkan(User $u): void
    {
        $u->forceFill(['dua_langkah_rahasia' => null, 'dua_langkah_aktif_at' => null, 'dua_langkah_cadangan' => null, 'dua_langkah_terakhir' => null])->save();
    }

    /** @return list<string> kode cadangan berbentuk XXXX-XXXX */
    private function buatCadangan(): array
    {
        return array_map(function () {
            $k = strtoupper(Str::random(8));
            $k = strtr($k, ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'Q']); // hindari karakter mirip

            return substr($k, 0, 4).'-'.substr($k, 4);
        }, range(1, self::JUMLAH_CADANGAN));
    }
}
