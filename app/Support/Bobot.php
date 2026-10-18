<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Bobot nilai akhir: kehadiran, tugas, kuis, UTS, UAS (persen, jumlahnya harus 100). */
class Bobot
{
    public const KOLOM = ['bobot_hadir' => 'Kehadiran', 'bobot_tugas' => 'Tugas', 'bobot_kuis' => 'Kuis', 'bobot_uts' => 'UTS', 'bobot_uas' => 'UAS'];

    public static function validasi(Request $request): array
    {
        $request->mergeIfMissing(['bobot_kuis' => 0]); // form lama tanpa kolom kuis
        $data = $request->validate(array_fill_keys(array_keys(self::KOLOM), 'required|integer|min:0|max:100'));
        $data = array_map('intval', $data);
        if (array_sum($data) !== 100) {
            throw ValidationException::withMessages(['bobot_hadir' => 'Jumlah bobot kehadiran, tugas, kuis, UTS, dan UAS harus 100% (sekarang '.array_sum($data).'%).']);
        }

        return $data;
    }
}
