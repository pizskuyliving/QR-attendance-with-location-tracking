<?php

namespace App\Services;

use App\Models\AppSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal kerja yang diatur admin di halaman Pengaturan.
 * Kalau belum pernah diatur, memakai nilai bawaan dari config/absen.php (.env).
 */
class WorkSchedule
{
    private const CACHE_KEY = 'absen.app_settings';

    private ?array $values = null;

    public function workStart(): string
    {
        return $this->get('work_start', config('absen.work_start', '08:00'));
    }

    public function workEnd(): string
    {
        return $this->get('work_end', config('absen.work_end', '17:00'));
    }

    public function lateTolerance(): int
    {
        return (int) $this->get('late_tolerance', config('absen.late_tolerance', 15));
    }

    public function minMinutesBeforeCheckout(): int
    {
        return (int) $this->get('min_minutes_before_checkout', config('absen.min_minutes_before_checkout', 60));
    }

    /** @return int[] 1 = Senin ... 7 = Minggu */
    public function workDays(): array
    {
        $raw = $this->get('work_days', null);
        $days = $raw !== null ? explode(',', $raw) : config('absen.work_days', [1, 2, 3, 4, 5]);

        return array_values(array_map('intval', array_filter($days, fn ($d) => $d !== '')));
    }

    /** Batas akhir masuk tepat waktu pada tanggal tertentu. */
    public function lateLimit(CarbonInterface $day): CarbonImmutable
    {
        return CarbonImmutable::parse($day->toDateString().' '.$this->workStart())
            ->addMinutes($this->lateTolerance());
    }

    /** Jam pulang pada tanggal tertentu. */
    public function endTime(CarbonInterface $day): CarbonImmutable
    {
        return CarbonImmutable::parse($day->toDateString().' '.$this->workEnd());
    }

    /** Simpan beberapa pengaturan sekaligus. */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    private function get(string $key, mixed $default): mixed
    {
        $this->values ??= $this->load();

        return $this->values[$key] ?? $default;
    }

    private function load(): array
    {
        // Aman dipanggil sebelum migration dijalankan.
        if (! Schema::hasTable('app_settings')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => AppSetting::pluck('value', 'key')->all());
    }
}
