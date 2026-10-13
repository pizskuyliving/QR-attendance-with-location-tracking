<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Bobot nilai akhir: kehadiran, tugas, UTS, UAS (persen, jumlahnya harus 100). */
class Bobot
{
    public const KOLOM = ['bobot_hadir' => 'Kehadiran', 'bobot_tugas' => 'Tugas', 'bobot_uts' => 'UTS', 'bobot_uas' => 'UAS'];

    public static function validasi(Request $request): array
    {
        $data = $request->validate(array_fill_keys(array_keys(self::KOLOM), 'required|integer|min:0|max:100'));
        $data = array_map('intval', $data);
        if (array_sum($data) !== 100) {
            throw ValidationException::withMessages(['bobot_hadir' => 'Jumlah bobot kehadiran, tugas, UTS, dan UAS harus 100% (sekarang '.array_sum($data).'%).']);
        }

        return $data;
    }
}
