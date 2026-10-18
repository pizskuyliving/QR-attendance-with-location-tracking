<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesi di perangkat lain ikut berakhir saat kata sandi diganti (ganti sendiri, lupa sandi, atau direset laboran).
 * Tiap sesi menyimpan sidik kata sandi saat login; bila tidak cocok lagi dengan kata sandi sekarang, sesi dikeluarkan.
 * Dipasang per guard: ->middleware('sesi.sandi:mahasiswa') / ('sesi.sandi:web').
 */
class SesiSandi
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $user = Auth::guard($guard)->user();
        if (! $user) {
            return $next($request);
        }

        $kunci = 'sidik_sandi_'.$guard;
        $tersimpan = $request->session()->get($kunci);
        // sidik milik akun lain (berganti akun di sesi yang sama) diabaikan, lalu ditimpa di bawah
        if (($tersimpan['id'] ?? null) === $user->getAuthIdentifier() && ! hash_equals(self::sidik($user->getAuthPassword()), $tersimpan['sidik'])) {
            Auth::guard($guard)->logoutCurrentDevice();
            $request->session()->forget($kunci);
            $login = $guard === 'web' ? 'asisten.login' : 'login';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'pesan' => 'Kata sandi akun ini sudah diganti. Silakan masuk lagi.'], 401)
                : redirect()->route($login)->withErrors(['email' => 'Kata sandi akun ini sudah diganti. Silakan masuk lagi.']);
        }

        $response = $next($request);

        // simpan sidik terbaru: sesi yang mengganti kata sandinya sendiri tetap masuk
        if ($user = Auth::guard($guard)->user()) {
            $request->session()->put($kunci, ['id' => $user->getAuthIdentifier(), 'sidik' => self::sidik($user->getAuthPassword())]);
        }

        return $response;
    }

    private static function sidik(string $hash): string
    {
        return hash_hmac('sha256', $hash, config('app.key'));
    }
}
