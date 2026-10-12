<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Asisten mendaftar sendiri; akun baru berstatus "menunggu" sampai disetujui admin. */
class DaftarAsistenController extends Controller
{
    public function form()
    {
        if (Auth::check()) {
            return redirect()->route('asisten.dashboard');
        }

        return view('auth.daftar-asisten');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:100', 'unique:users,email'],
            'keterangan' => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Jika pendaftaranmu belum disetujui, tunggu atau hubungi admin.',
        ], [
            'name' => 'nama', 'keterangan' => 'mata kuliah yang diampu', 'password' => 'kata sandi',
        ]);
        if ($galat = Captcha::periksa($request)) {
            return back()->withErrors(['captcha' => $galat])->withInput($request->only('name', 'email', 'keterangan'));
        }

        $u = User::create($data + ['role' => 'asisten', 'status' => 'menunggu']);
        LogAktivitas::catat(null, 'asisten_daftar', "{$u->name} ({$u->email}) mendaftar sebagai asisten, menunggu persetujuan admin");

        return redirect()->route('asisten.login')
            ->with('ok', 'Pendaftaran terkirim. Kamu bisa masuk setelah admin menyetujui akunmu.');
    }
}
