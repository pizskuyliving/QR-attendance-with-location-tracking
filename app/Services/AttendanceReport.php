<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Perhitungan rekap absen: status harian, ringkasan bulanan.
 *
 * Status yang dipakai:
 *   hadir      -> absen masuk tepat waktu
 *   terlambat  -> absen masuk lewat jam masuk + toleransi
 *   alpa       -> hari kerja yang sudah lewat tanpa absen
 *   belum      -> hari ini, belum absen
 *   libur      -> bukan hari kerja dan tidak absen
 */
class AttendanceReport
{
    public const LABELS = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'alpa' => 'Tidak hadir',
        'belum' => 'Belum absen',
        'libur' => 'Libur',
    ];

    public const BADGES = [
        'hadir' => 'badge-ok',
        'terlambat' => 'badge-warn',
        'alpa' => 'badge-err',
        'belum' => '',
        'libur' => '',
    ];

    /** "2026-10" dari input, atau bulan ini kalau kosong/tidak valid. */
    public function month(?string $input): CarbonImmutable
    {
        if ($input && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $input)) {
            return CarbonImmutable::createFromFormat('Y-m-d', $input.'-01')->startOfDay();
        }

        return CarbonImmutable::today()->startOfMonth();
    }

    /** "2026-10-09" dari input, atau hari ini kalau kosong/tidak valid. */
    public function day(?string $input): CarbonImmutable
    {
        if ($input && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input) && strtotime($input)) {
            return CarbonImmutable::parse($input)->startOfDay();
        }

        return CarbonImmutable::today();
    }

    public function __construct(private WorkSchedule $schedule)
    {
    }

    public function isWorkDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->schedule->workDays(), true);
    }

    /** Status satu hari untuk satu pegawai. */
    public function statusFor(CarbonInterface $date, ?Attendance $record): string
    {
        if ($record) {
            return $record->status; // hadir | terlambat
        }

        if (! $this->isWorkDay($date)) {
            return 'libur';
        }

        // Hari ini / tanggal yang belum tiba: belum bisa dianggap tidak hadir.
        return ($date->isToday() || $date->isFuture()) ? 'belum' : 'alpa';
    }

    /**
     * Rentang tanggal yang dihitung untuk satu bulan:
     * awal bulan s/d akhir bulan, tapi tidak melewati hari ini.
     * Mengembalikan null kalau bulannya masih di masa depan.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function range(CarbonImmutable $month): ?array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth()->startOfDay();
        $today = CarbonImmutable::today();

        if ($start->greaterThan($today)) {
            return null;
        }

        return [$start, $end->greaterThan($today) ? $today : $end];
    }

    /** Absen dalam rentang, dikelompokkan per user lalu per tanggal (Y-m-d). */
    public function records(CarbonInterface $start, CarbonInterface $end, ?int $userId = null): Collection
    {
        return Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items->keyBy(fn (Attendance $a) => $a->date->toDateString()));
    }

    /**
     * Baris per hari untuk satu pegawai dalam satu bulan (terbaru di atas).
     *
     * @return array<int, array{date: CarbonImmutable, record: ?Attendance, status: string}>
     */
    public function dailyRows(User $user, CarbonImmutable $month): array
    {
        $range = $this->range($month);
        if (! $range) {
            return [];
        }

        [$start, $end] = $range;

        // Hari sebelum akun pegawai dibuat tidak ditampilkan.
        $since = $this->since($user);
        if ($since && $since->greaterThan($start)) {
            $start = $since;
        }
        if ($start->greaterThan($end)) {
            return [];
        }

        $records = $this->records($start, $end, $user->id)->get($user->id, collect());

        $rows = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $day = CarbonImmutable::instance($day);
            $record = $records->get($day->toDateString());
            $rows[] = ['date' => $day, 'record' => $record, 'status' => $this->statusFor($day, $record)];
        }

        return array_reverse($rows);
    }

    /** Tanggal akun pegawai dibuat (awal hari), atau null kalau tidak diketahui. */
    private function since(User $user): ?CarbonImmutable
    {
        return $user->created_at ? CarbonImmutable::instance($user->created_at)->startOfDay() : null;
    }

    /** Hitung jumlah tiap status dari baris harian. */
    public function countRows(array $rows): array
    {
        $counts = array_fill_keys(array_keys(self::LABELS), 0);
        $counts['pulang_cepat'] = 0;
        foreach ($rows as $row) {
            $counts[$row['status']]++;
            if ($row['record']?->early_leave) {
                $counts['pulang_cepat']++;
            }
        }

        return $counts;
    }

    /**
     * Ringkasan bulanan untuk banyak pegawai.
     *
     * @return array<int, array{user: User, hadir: int, terlambat: int, alpa: int, total_masuk: int}>
     */
    public function monthlySummary(Collection $users, CarbonImmutable $month): array
    {
        $range = $this->range($month);
        $summary = [];

        if (! $range) {
            foreach ($users as $user) {
                $summary[] = ['user' => $user, 'hadir' => 0, 'terlambat' => 0, 'alpa' => 0, 'pulang_cepat' => 0, 'total_masuk' => 0];
            }

            return $summary;
        }

        [$start, $end] = $range;
        $records = $this->records($start, $end);

        // Hari kerja yang sudah lewat (hari ini tidak dihitung alpa).
        $pastWorkDays = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($this->isWorkDay($day) && ! $day->isToday()) {
                $pastWorkDays[] = $day->toDateString();
            }
        }

        foreach ($users as $user) {
            $mine = $records->get($user->id, collect());
            $hadir = $mine->where('status', 'hadir')->count();
            $terlambat = $mine->where('status', 'terlambat')->count();
            $since = $this->since($user)?->toDateString();
            $alpa = count(array_filter(
                $pastWorkDays,
                fn ($d) => ! $mine->has($d) && (! $since || $d >= $since)
            ));

            $summary[] = [
                'user' => $user,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'alpa' => $alpa,
                'pulang_cepat' => $mine->where('early_leave', true)->count(),
                'total_masuk' => $hadir + $terlambat,
            ];
        }

        return $summary;
    }
}
