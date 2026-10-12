<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;

/**
 * Akun mahasiswa dummy untuk uji coba: 5 mahasiswa per kelas A–G, sudah terverifikasi,
 * belum mengikuti mata kuliah apa pun (dipilih sendiri setelah login).
 * Aman dijalankan berulang dan tidak menghapus data lain:
 *   php artisan db:seed --class=MahasiswaDummySeeder
 *
 * Login: {npm}@uji.ac.id, kata sandi: uji12345. NPM 604{n}10{01-05}, n = 1 (A) s.d. 7 (G).
 */
class MahasiswaDummySeeder extends Seeder
{
    public const SANDI = 'uji12345';

    private const NAMA = ['Rizky Pratama', 'Ayu Lestari', 'Fajar Nugroho', 'Nadia Putri', 'Bagus Setiawan'];

    public function run(): void
    {
        foreach (config('absen.kelas') as $i => $kelas) {
            foreach (self::NAMA as $j => $nama) {
                $npm = '604'.($i + 1).'10'.str_pad($j + 1, 2, '0', STR_PAD_LEFT);
                Mahasiswa::updateOrCreate(['npm' => $npm], [
                    'nama' => $nama.' '.$kelas,
                    'kelas' => $kelas,
                    'email' => $npm.'@uji.ac.id',
                    'email_verified_at' => now(),
                    'password' => self::SANDI,
                ]);
            }
        }

        $this->command?->info('Akun dummy siap: 60411001@uji.ac.id s.d. 60471005@uji.ac.id, kata sandi '.self::SANDI);
    }
}
