<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function show(Request $request)
    {
        return view('asisten.profil', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($u->id)],
        ]);
        // email dipakai untuk lupa sandi: menggantinya wajib dengan kata sandi, agar sesi curian tidak bisa mengambil alih akun
        if ($data['email'] !== $u->email) {
            $request->validate(['password_lama' => 'required|current_password:web'], [
                'password_lama.required' => 'Masukkan kata sandi untuk mengganti email.',
                'password_lama.current_password' => 'Kata sandi salah.',
            ]);
            LogAktivitas::catat(null, 'ganti_email', "{$u->name} mengganti email akun dari {$u->email} ke {$data['email']}");
        }
        $u->update($data);

        return back()->with('ok', 'Profil diperbarui.');
    }

    // ---- Kelola akun (khusus laboran; halaman Akun di LaboranController::akun) ----

    public function tambahAsisten(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:admin,asisten,dosen',
        ]);
        $u = User::create($data);
        LogAktivitas::catat(null, 'akun_tambah', "{$request->user()->name} membuat akun {$u->peranLabel()} {$u->name} ({$u->email})");

        return back()->with('ok', "Akun {$data['name']} dibuat.");
    }

    public function resetAsisten(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['password' => ['required', Password::defaults()]]);
        $user->password = $data['password'];
        $user->setRememberToken(\Illuminate\Support\Str::random(60)); // "ingat saya" di perangkat lain tidak berlaku lagi
        $user->save();
        LogAktivitas::catat(null, 'reset_sandi_akun', "{$request->user()->name} mengganti kata sandi akun {$user->name} ({$user->email})");

        return back()->with('ok', "Kata sandi {$user->name} diganti.");
    }

    public function setujui(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $user->update(['status' => 'aktif']);
        LogAktivitas::catat(null, 'asisten_setujui', "{$request->user()->name} menyetujui akun asisten {$user->name} ({$user->email})");

        return back()->with('ok', "Akun {$user->name} disetujui dan sudah bisa masuk.");
    }

    public function tolak(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_if($user->id === $request->user()->id, 422);
        $user->status = 'ditolak';
        $user->setRememberToken(\Illuminate\Support\Str::random(60)); // "ingat saya" miliknya tidak berlaku lagi
        $user->save();
        LogAktivitas::catat(null, 'asisten_tolak', "{$request->user()->name} menolak pendaftaran asisten {$user->name} ({$user->email})");

        return back()->with('ok', "Pendaftaran {$user->name} ditolak.");
    }

    public function hapusAsisten(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menghapus akun sendiri.');
        // kelas lama milik asisten (tanpa matkul) ikut dihapus lewat model agar file materi & kiriman tugas terhapus;
        // kelas di matkul dosen tetap ada dan menjadi "belum ada asisten"
        $user->kelas()->whereNull('matkul_id')->get()->each->delete();
        $user->delete();
        LogAktivitas::catat(null, 'akun_hapus', "{$request->user()->name} menghapus akun {$user->peranLabel()} {$user->name} ({$user->email})");

        return back()->with('ok', "Akun {$user->name} dihapus.");
    }
}
