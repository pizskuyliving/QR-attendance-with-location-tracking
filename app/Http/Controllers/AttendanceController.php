<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Office;
use App\Services\AttendanceReport;
use App\Services\QrTokenService;
use App\Services\WorkSchedule;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private WorkSchedule $schedule)
    {
    }

    /** Halaman scan untuk pegawai. */
    public function scan(Request $request): View
    {
        $today = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', today())
            ->first();

        return view('absen.scan', [
            'today' => $today,
            'workStart' => $this->schedule->workStart(),
            'workEnd' => $this->schedule->workEnd(),
        ]);
    }

    /** Riwayat absen milik pegawai yang login, per bulan. */
    public function history(Request $request, AttendanceReport $report): View
    {
        $month = $report->month($request->query('bulan'));
        $rows = $report->dailyRows($request->user(), $month);

        return view('absen.riwayat', [
            'month' => $month,
            'rows' => $rows,
            'counts' => $report->countRows($rows),
        ]);
    }

    /** Menerima hasil scan: token QR + lokasi GPS. */
    public function store(Request $request, QrTokenService $qr): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        // 1. Token QR sah dan belum kedaluwarsa?
        $officeId = $qr->verify($data['token']);
        if (! $officeId) {
            return $this->fail('QR tidak valid atau sudah kedaluwarsa. Scan QR terbaru di layar.');
        }

        $office = Office::where('is_active', true)->find($officeId);
        if (! $office) {
            return $this->fail('Kantor untuk QR ini tidak aktif.');
        }

        // Pegawai yang terdaftar di kantor tertentu hanya boleh absen di kantornya.
        if ($user->office_id && $user->office_id !== $office->id) {
            return $this->fail('QR ini bukan untuk kantor Anda.');
        }

        // 2. Akurasi GPS cukup?
        $maxAccuracy = (int) config('absen.max_gps_accuracy', 100);
        if (isset($data['accuracy']) && $data['accuracy'] > $maxAccuracy) {
            return $this->fail('Sinyal GPS kurang akurat (±'.round((float) $data['accuracy']).' m). Coba di dekat jendela atau aktifkan Lokasi Akurat.');
        }

        // 3. Di dalam radius kantor?
        $distance = $office->distanceTo((float) $data['latitude'], (float) $data['longitude']);
        if ($distance > $office->radius) {
            return $this->fail("Anda berada {$distance} m dari kantor. Batas absen {$office->radius} m.", 'luar_area');
        }

        // 4. Catat absen masuk / pulang.
        return DB::transaction(function () use ($user, $office, $data, $distance) {
            $now = now();

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                    'date' => $now->toDateString(),
                    'check_in_at' => $now,
                    'check_in_lat' => $data['latitude'],
                    'check_in_lng' => $data['longitude'],
                    'check_in_distance' => $distance,
                    'status' => $this->statusFor($now),
                ]);

                return $this->ok(
                    'masuk',
                    'Absen masuk tercatat pukul '.$now->format('H:i').
                    ($attendance->status === 'terlambat' ? ' (terlambat).' : '.'),
                    $attendance
                );
            }

            if ($attendance->check_out_at) {
                return $this->fail('Anda sudah absen masuk dan pulang hari ini.');
            }

            $minMinutes = $this->schedule->minMinutesBeforeCheckout();
            $elapsed = $attendance->check_in_at->diffInMinutes($now);
            if ($elapsed < $minMinutes) {
                return $this->fail('Anda sudah absen masuk pukul '.$attendance->check_in_at->format('H:i').
                    ". Absen pulang bisa dilakukan minimal {$minMinutes} menit setelah masuk.");
            }

            $earlyLeave = $now->lessThan($this->schedule->endTime($now));

            $attendance->update([
                'check_out_at' => $now,
                'check_out_lat' => $data['latitude'],
                'check_out_lng' => $data['longitude'],
                'check_out_distance' => $distance,
                'early_leave' => $earlyLeave,
            ]);

            return $this->ok(
                'pulang',
                'Absen pulang tercatat pukul '.$now->format('H:i').
                ($earlyLeave ? ' (sebelum jam pulang '.$this->schedule->workEnd().').' : '.'),
                $attendance
            );
        });
    }

    private function statusFor(CarbonInterface $time): string
    {
        return $time->greaterThan($this->schedule->lateLimit($time)) ? 'terlambat' : 'hadir';
    }

    private function ok(string $type, string $message, Attendance $a): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'type' => $type,
            'message' => $message,
            'check_in' => $a->check_in_at?->format('H:i'),
            'check_out' => $a->check_out_at?->format('H:i'),
            'early_leave' => (bool) $a->early_leave,
            'status' => $a->status,
        ]);
    }

    private function fail(string $message, string $code = 'gagal'): JsonResponse
    {
        return response()->json(['ok' => false, 'code' => $code, 'message' => $message], 422);
    }
}
