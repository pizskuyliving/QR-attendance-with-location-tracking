<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman admin: absensi harian, rekap bulanan, export Excel.
 */
class AdminAttendanceController extends Controller
{
    public function __construct(private AttendanceReport $report)
    {
    }

    /** Siapa saja yang sudah/belum absen pada satu tanggal. */
    public function daily(Request $request): View
    {
        $date = $this->report->day($request->query('tanggal'));

        $records = Attendance::whereDate('date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $this->employees()
            // Pegawai yang akunnya dibuat setelah tanggal ini tidak ditampilkan.
            ->filter(fn (User $u) => ! $u->created_at || $u->created_at->toDateString() <= $date->toDateString())
            ->values()
            ->map(function (User $user) use ($records, $date) {
            $record = $records->get($user->id);

            return [
                'user' => $user,
                'record' => $record,
                'status' => $this->report->statusFor($date, $record),
            ];
        });

        return view('absen.admin.harian', [
            'date' => $date,
            'rows' => $rows,
            'counts' => $this->report->countRows($rows->all()),
        ]);
    }

    /** Rekap per bulan; kalau pegawai dipilih, tampilkan rincian hariannya. */
    public function monthly(Request $request): View
    {
        return view('absen.admin.rekap', $this->monthlyData($request));
    }

    /** Export rekap yang sedang ditampilkan ke file Excel (.xls). */
    public function export(Request $request): Response
    {
        $data = $this->monthlyData($request);

        $name = 'rekap-absen-'.$data['month']->format('Y-m')
            .($data['selected'] ? '-'.str($data['selected']->name)->slug() : '')
            .'.xls';

        // Tabel HTML dengan tipe Excel: terbuka rapi di Excel versi/bahasa apa pun.
        $html = view('absen.admin.export', $data)->render();

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function monthlyData(Request $request): array
    {
        $month = $this->report->month($request->query('bulan'));
        $employees = $this->employees();

        $selected = null;
        if ($request->filled('pegawai')) {
            $selected = $employees->firstWhere('id', (int) $request->query('pegawai'));
        }

        $rows = $selected ? $this->report->dailyRows($selected, $month) : [];

        return [
            'month' => $month,
            'employees' => $employees,
            'selected' => $selected,
            'summary' => $selected ? [] : $this->report->monthlySummary($employees, $month),
            'rows' => $rows,
            'counts' => $this->report->countRows($rows),
        ];
    }

    private function employees(): Collection
    {
        return User::where('role', 'pegawai')->orderBy('name')->get();
    }
}
