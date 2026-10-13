<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Akun asisten yang ditolak/dinonaktifkan admin langsung keluar, walau sesinya masih ada. */
class AsistenAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('web')->user();
        if ($user && in_array($user->status, ['menunggu', 'ditolak'], true)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('asisten.login')->withErrors(['email' => 'Akun ini tidak aktif. Hubungi laboran.']);
        }

        return $next($request);
    }
}
