<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Support\Kehadiran;

/**
 * Menyusun rekap absen satu kelas: grid mahasiswa x pertemuan,
 * total per status, persentase per mahasiswa, dan ringkasan per pertemuan.
 */
class Rekap
{
    public array $baris = [];

    public array $perPertemuan = [];

    public function __construct(public Kelas $kelas)
    {
        $n = $kelas->jumlah_pertemuan;
        $absen = Absensi::where('kelas_id', $kelas->id)->get()
            ->groupBy('mahasiswa_id');

        for ($p = 1; $p <= $n; $p++) {
            $this->perPertemuan[$p] = ['H' => 0, 'I' => 0, 'S' => 0, 'A' => 0, 'kosong' => 0];
        }

        foreach ($kelas->mahasiswa as $mhs) {
            $sel = [];
            $total = ['H' => 0, 'I' => 0, 'S' => 0, 'A' => 0];
            $catatan = ($absen[$mhs->id] ?? collect())->keyBy('pertemuan');
            for ($p = 1; $p <= $n; $p++) {
                $a = $catatan[$p] ?? null;
                $sel[$p] = $a;
                if ($a) {
                    $total[$a->status]++;
                    $this->perPertemuan[$p][$a->status]++;
                } else {
                    $this->perPertemuan[$p]['kosong']++;
                }
            }
            $terisi = array_sum($total);
            $this->baris[] = [
                'mahasiswa' => $mhs,
                'sel' => $sel,
                'total' => $total,
                // persentase dihitung terhadap seluruh pertemuan
                'persen' => $n ? round($total['H'] / $n * 100, 1) : 0,
                'terisi' => $terisi,
                'kehadiran' => Kehadiran::untuk($kelas, $catatan->pluck('status')),
            ];
        }
    }

    /** Mahasiswa yang jatah tidak hadirnya hampir habis atau sudah lewat, paling parah dulu. */
    public function terancam(): array
    {
        $b = array_filter($this->baris, fn ($r) => $r['kehadiran']->level() !== 'aman');
        usort($b, fn ($x, $y) => $x['kehadiran']->sisa() <=> $y['kehadiran']->sisa());

        return $b;
    }

    public function pertemuanTerlaksana(): int
    {
        return count(array_filter($this->perPertemuan, fn ($r) => $r['H'] + $r['I'] + $r['S'] + $r['A'] > 0));
    }

    public function rataRataKehadiran(): float
    {
        if (! $this->baris) {
            return 0;
        }

        return round(array_sum(array_column($this->baris, 'persen')) / count($this->baris), 1);
    }
}
