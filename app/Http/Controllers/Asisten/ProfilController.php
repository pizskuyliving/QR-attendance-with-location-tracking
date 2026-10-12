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
        $admin = $request->user()->role === 'admin';
        $asisten = $admin ? User::withCount('kelas')->where('status', 'aktif')->orderBy('name')->get() : collect();
        $menunggu = $admin ? User::where('status', 'menunggu')->oldest()->get() : collect();
        $ditolak = $admin ? User::where('status', 'ditolak')->latest('updated_at')->get() : collect();

        return view('asisten.profil', ['user' => $request->user()] + compact('asisten', 'menunggu', 'ditolak'));
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $u->update($request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($u->id)],
        ]));

        return back()->with('ok', 'Profil diperbarui.');
    }

    // ---- Kelola akun asisten (khusus admin) ----

    public function tambahAsisten(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:admin,asisten',
        ]);
        User::create($data);

        return back()->with('ok', "Akun {$data['name']} dibuat.");
    }

    public function resetAsisten(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['password' => ['required', Password::defaults()]]);
        $user->update($data);

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
        // hapus lewat model agar file materi & kiriman tugas di kelasnya ikut terhapus dari penyimpanan
        $user->kelas()->get()->each->delete();
        $user->delete();

        return back()->with('ok', "Akun {$user->name} dihapus beserta kelasnya.");
    }
}
