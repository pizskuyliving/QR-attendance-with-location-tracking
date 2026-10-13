<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Batasi rute ke peran tertentu, mis. ->middleware('peran:admin,asisten'). */
class Peran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        if (! in_array($request->user()?->role, $peran, true)) {
            // membuka halaman lewat tautan/riwayat: arahkan ke beranda akun ini, bukan layar 403 polos
            if ($request->isMethod('GET') && ! $request->expectsJson() && $request->user()) {
                return redirect()->route($request->user()->role === 'dosen' ? 'asisten.matkul.index' : 'asisten.dashboard')->withErrors(['peran' => 'Halaman tadi bukan untuk peran akunmu, jadi kamu diarahkan ke beranda.']);
            }
            abort(403, 'Halaman ini bukan untuk peran akunmu.');
        }

        return $next($request);
    }
}
