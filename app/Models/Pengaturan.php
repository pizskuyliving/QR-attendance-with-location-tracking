<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan sistem yang diatur laboran (menu Sistem). Disimpan sebagai JSON per kunci,
 * lalu dipasang ke config('absen.*') saat aplikasi mulai, jadi kode lain cukup membaca config.
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    protected $casts = ['nilai' => 'json'];

    /** Kunci yang boleh diubah dari aplikasi, dipetakan ke config('absen.*'). */
    public const KUNCI = ['institusi', 'domain_email', 'daftar_mahasiswa', 'daftar_asisten', 'maks_unggah_kb', 'huruf'];

    private const CACHE = 'flex.pengaturan';

    public static function terapkan(): void
    {
        try {
            $semua = Cache::rememberForever(self::CACHE, fn () => Schema::hasTable('pengaturan')
                ? self::query()->whereIn('kunci', self::KUNCI)->pluck('nilai', 'kunci')->all()
                : []);
        } catch (\Throwable) {
            return; // database belum siap (mis. saat instalasi): pakai nilai bawaan
        }
        foreach ($semua as $kunci => $nilai) {
            if ($nilai !== null) {
                config(["absen.$kunci" => $nilai]);
            }
        }
    }

    public static function simpan(array $data): void
    {
        foreach (array_intersect_key($data, array_flip(self::KUNCI)) as $kunci => $nilai) {
            self::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }
        Cache::forget(self::CACHE);
        self::terapkan();
    }
}
