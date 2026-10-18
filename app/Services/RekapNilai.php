<?php

namespace App\Services;

use App\Models\KuisJawaban;
use App\Models\Pengumpulan;

/**
 * Nilai akhir per mahasiswa = bobot_hadir% × persentase hadir + bobot_tugas% × rata-rata tugas
 *   + bobot_kuis% × rata-rata kuis + bobot_uts% × nilai UTS + bobot_uas% × nilai UAS.
 * Komponen yang belum ada nilainya (belum ada tugas dihitung, UTS/UAS belum diisi) dilewati dan bobotnya dibagi ke komponen lain.
 * Tugas yang dihitung: yang sudah lewat tenggat, atau yang sudah dinilai.
 * Tidak mengumpulkan tugas yang sudah lewat tenggat = 0. Sudah mengumpulkan tapi belum dinilai = belum dihitung (ditandai).
 * Kuis yang dihitung: yang sudah selesai (waktunya habis). Tidak menjawab (termasuk tidak hadir) = 0.
 */
class RekapNilai
{
    /** @var list<array{mahasiswa:\App\Models\Mahasiswa, hadir:float, tugas:array<int,?float>, rata_tugas:?float, belum_dinilai:int, akhir:?float, huruf:?string}> */
    public array $baris = [];

    public $tugas;

    /** Kuis yang sudah selesai, urut pertemuan. */
    public $kuis;

    public function __construct(public Rekap $rekap)
    {
        $kelas = $rekap->kelas;
        $this->tugas = $kelas->tugas()->get();
        $kiriman = Pengumpulan::whereIn('tugas_id', $this->tugas->pluck('id'))->get()
            ->groupBy('mahasiswa_id');
        $this->kuis = $kelas->kuis()->whereNotNull('dibuka_at')->where('ditutup_at', '<=', now())->get();
        $nilaiKuis = KuisJawaban::whereIn('kuis_id', $this->kuis->pluck('id'))->toBase()->get(['kuis_id', 'mahasiswa_id', 'nilai'])
            ->groupBy('mahasiswa_id')->map(fn ($j) => $j->pluck('nilai', 'kuis_id'));

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
            $kuis = [];
            foreach ($this->kuis as $q) {
                $kuis[$q->id] = (float) ($nilaiKuis[$mhs->id][$q->id] ?? 0);
            }
            $rataKuis = $kuis ? round(array_sum($kuis) / count($kuis), 1) : null;
            $uts = $mhs->pivot?->nilai_uts === null ? null : (float) $mhs->pivot->nilai_uts;
            $uas = $mhs->pivot?->nilai_uas === null ? null : (float) $mhs->pivot->nilai_uas;
            $akhir = self::akhir([
                [$b['persen'], (int) $kelas->bobot_hadir],
                [$rataTugas, (int) $kelas->bobot_tugas],
                [$rataKuis, (int) $kelas->bobot_kuis],
                [$uts, (int) $kelas->bobot_uts],
                [$uas, (int) $kelas->bobot_uas],
            ]);

            $this->baris[] = [
                'mahasiswa' => $mhs,
                'hadir' => $b['persen'],
                'tugas' => $nilaiTugas,
                'kiriman' => $milik,
                'rata_tugas' => $rataTugas,
                'kuis' => $kuis,
                'rata_kuis' => $rataKuis,
                'uts' => $uts,
                'uas' => $uas,
                'belum_dinilai' => $belumDinilai,
                'akhir' => $akhir,
                'huruf' => $akhir === null ? null : self::huruf($akhir),
            ];
        }
    }

    /**
     * Rata-rata berbobot dari komponen [nilai, bobot]. Komponen tanpa nilai (null) tidak dihitung,
     * jadi nilai akhir sementara tetap masuk akal sebelum semua tugas/ujian selesai.
     *
     * @param  list<array{0:?float,1:int}>  $komponen
     */
    public static function akhir(array $komponen): ?float
    {
        $ada = array_filter($komponen, fn ($k) => $k[0] !== null && $k[1] > 0);
        $total = array_sum(array_column($ada, 1));
        if ($total === 0) {
            return null;
        }

        return round(array_sum(array_map(fn ($k) => $k[0] * $k[1], $ada)) / $total, 1);
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
