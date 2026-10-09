<?php

namespace App\Http\Controllers;

use App\Services\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Pengaturan (admin): jam masuk, jam pulang, toleransi, hari kerja.
 */
class AdminSettingsController extends Controller
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function edit(WorkSchedule $schedule): View
    {
        return view('absen.admin.pengaturan', [
            'workStart' => $schedule->workStart(),
            'workEnd' => $schedule->workEnd(),
            'lateTolerance' => $schedule->lateTolerance(),
            'minCheckout' => $schedule->minMinutesBeforeCheckout(),
            'workDays' => $schedule->workDays(),
            'days' => self::DAYS,
        ]);
    }

    public function update(Request $request, WorkSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'late_tolerance' => ['required', 'integer', 'min:0', 'max:240'],
            'min_minutes_before_checkout' => ['required', 'integer', 'min:0', 'max:720'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'between:1,7'],
        ], [
            'work_end.after' => 'Jam pulang harus setelah jam masuk.',
            'work_days.required' => 'Pilih minimal satu hari kerja.',
            'work_start.date_format' => 'Format jam masuk harus JJ:MM, contoh 08:00.',
            'work_end.date_format' => 'Format jam pulang harus JJ:MM, contoh 17:00.',
        ]);

        $days = array_map('intval', $data['work_days']);
        sort($days);

        $schedule->save([
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'late_tolerance' => $data['late_tolerance'],
            'min_minutes_before_checkout' => $data['min_minutes_before_checkout'],
            'work_days' => implode(',', array_unique($days)),
        ]);

        return redirect()->route('admin.pengaturan')->with('status', 'Pengaturan disimpan.');
    }
}
