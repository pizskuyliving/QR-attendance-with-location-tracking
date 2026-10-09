<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Services\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Layar QR di kantor (monitor/tablet), dibuka oleh akun admin.
 */
class QrDisplayController extends Controller
{
    public function show(Office $office): View
    {
        return view('absen.qr-display', [
            'office' => $office,
        ]);
    }

    /** Dipanggil JavaScript layar QR untuk mengambil token terbaru. */
    public function token(Office $office, QrTokenService $qr): JsonResponse
    {
        abort_unless($office->is_active, 404);

        return response()->json([
            'token' => $qr->make($office->id),
            'seconds_left' => $qr->secondsLeft(),
            'period' => $qr->period(),
            'server_time' => now()->format('H:i:s'),
        ])->header('Cache-Control', 'no-store');
    }
}
