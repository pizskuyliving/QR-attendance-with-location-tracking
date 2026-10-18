<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Asisten mendaftar sendiri; akun baru berstatus "menunggu" sampai disetujui laboran. */
class DaftarAsistenController extends Controller
{
    public function form()
    {
        if (Auth::check()) {
            return redirect()->route('asisten.dashboard');
        }
        if (! config('absen.daftar_asisten')) {
            return RegisterController::ditutup('asisten');
        }

        return view('auth.daftar-asisten');
    }

    public function store(Request $request)
    {
        if (! config('absen.daftar_asisten')) {
            return RegisterController::ditutup('asisten');
        }
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100', 'unique:users,email'],
            'keterangan' => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Jika pendaftaranmu belum disetujui, tunggu atau hubungi laboran.',
        ], [
            'name' => 'nama', 'keterangan' => 'mata kuliah yang diampu', 'password' => 'kata sandi',
        ]);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->withInput($request->only('name', 'email', 'keterangan'));
        }

        $u = User::create($data + ['role' => 'asisten', 'status' => 'menunggu']);
        LogAktivitas::catat(null, 'asisten_daftar', "{$u->name} ({$u->email}) mendaftar sebagai asisten, menunggu persetujuan laboran");

        return redirect()->route('asisten.login')
            ->with('ok', 'Pendaftaran terkirim. Kamu bisa masuk setelah laboran menyetujui akunmu.');
    }
}
