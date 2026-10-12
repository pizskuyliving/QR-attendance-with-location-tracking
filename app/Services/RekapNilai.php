<?php

namespace App\Services;

use App\Models\Pengumpulan;

/**
 * Nilai akhir per mahasiswa = bobot_hadir% × persentase hadir + bobot_tugas% × rata-rata tugas.
 * Tugas yang dihitung: yang sudah lewat tenggat, atau yang sudah dinilai.
 * Tidak mengumpulkan tugas yang sudah lewat tenggat = 0. Sudah mengumpulkan tapi belum dinilai = belum dihitung (ditandai).
 */
class RekapNilai
{
    /** @var list<array{mahasiswa:\App\Models\Mahasiswa, hadir:float, tugas:array<int,?float>, rata_tugas:?float, belum_dinilai:int, akhir:?float, huruf:?string}> */
    public array $baris = [];

    public $tugas;

    public function __construct(public Rekap $rekap)
    {
        $kelas = $rekap->kelas;
        $this->tugas = $kelas->tugas()->get();
        $kiriman = Pengumpulan::whereIn('tugas_id', $this->tugas->pluck('id'))->get()
            ->groupBy('mahasiswa_id');

        foreach ($rekap->baris as $b) {
            $mhs = $b['mahasiswa'];
            $milik = ($kiriman[$mhs->id] ?? collect())->keyBy('tugas_id');
            $nilaiTugas = [];
            $dihitung = [];
            $belumDinilai = 0;
            foreach ($this->tugas as $t) {
                $p = $milik[$t->id] ?? null;
                if ($p?->dinilai()) {
                    $nilaiTugas[$t->id] = $p->nilai;
                    $dihitung[] = $p->nilai;
                } elseif ($p) {
                    $nilaiTugas[$t->id] = null;
                    $belumDinilai++;
                } elseif ($t->lewatTenggat()) {
                    $nilaiTugas[$t->id] = 0.0;
                    $dihitung[] = 0.0;
                } else {
                    $nilaiTugas[$t->id] = null;
                }
            }
            $rataTugas = $dihitung ? round(array_sum($dihitung) / count($dihitung), 1) : null;
            $akhir = self::akhir($b['persen'], $rataTugas, $kelas->bobot_hadir, $kelas->bobot_tugas);

            $this->baris[] = [
                'mahasiswa' => $mhs,
                'hadir' => $b['persen'],
                'tugas' => $nilaiTugas,
                'kiriman' => $milik,
                'rata_tugas' => $rataTugas,
                'belum_dinilai' => $belumDinilai,
                'akhir' => $akhir,
                'huruf' => $akhir === null ? null : self::huruf($akhir),
            ];
        }
    }

    public static function akhir(float $hadir, ?float $rataTugas, int $bobotHadir, int $bobotTugas): ?float
    {
        $total = $bobotHadir + $bobotTugas;
        if ($total === 0) {
            return null;
        }
        // belum ada tugas yang dihitung: nilai akhir hanya dari kehadiran
        if ($rataTugas === null) {
            return $bobotHadir ? round($hadir, 1) : null;
        }

        return round(($hadir * $bobotHadir + $rataTugas * $bobotTugas) / $total, 1);
    }

    public static function huruf(float $nilai): string
    {
        foreach (config('absen.huruf') as $h => $batas) {
            if ($nilai >= $batas) {
                return $h;
            }
        }

        return array_key_last(config('absen.huruf'));
    }
}
