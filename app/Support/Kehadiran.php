<?php

namespace App\Support;

use App\Models\Kelas;

/**
 * Jatah tidak hadir seorang mahasiswa di satu kelas.
 * Hanya status H yang dihitung hadir (sama dengan % hadir di rekap); I, S, dan A mengurangi jatah.
 */
final class Kehadiran
{
    public function __construct(public readonly int $tidakHadir, public readonly int $maks, public readonly int $minPersen) {}

    /** @param iterable<string> $status daftar status absen mahasiswa di kelas ini */
    public static function untuk(Kelas $kelas, iterable $status): self
    {
        $tidak = 0;
        foreach ($status as $s) {
            if ($s !== null && $s !== 'H') {
                $tidak++;
            }
        }

        return new self($tidak, $kelas->maksTidakHadir(), (int) $kelas->min_hadir);
    }

    public function sisa(): int
    {
        return $this->maks - $this->tidakHadir;
    }

    /** aman · waspada (sisa 1) · batas (sisa 0) · lewat (di bawah minimal) */
    public function level(): string
    {
        $sisa = $this->sisa();

        return match (true) {
            $sisa < 0 => 'lewat',
            $sisa === 0 && $this->tidakHadir > 0 => 'batas',
            $sisa === 1 && $this->tidakHadir > 0 => 'waspada',
            default => 'aman',
        };
    }

    public function teks(): string
    {
        return match ($this->level()) {
            'lewat' => "Melebihi batas: kehadiran di bawah {$this->minPersen}%",
            'batas' => 'Jatah tidak hadir habis, wajib hadir di sisa pertemuan',
            'waspada' => 'Tinggal 1 kali boleh tidak hadir',
            default => $this->sisa() > 0 ? "Boleh tidak hadir {$this->sisa()} kali lagi" : 'Wajib hadir di semua pertemuan',
        };
    }
}
