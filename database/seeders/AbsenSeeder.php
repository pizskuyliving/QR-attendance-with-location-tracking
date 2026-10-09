<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal untuk uji coba.
 * Jalankan: php artisan db:seed --class=AbsenSeeder
 *
 * GANTI latitude/longitude dengan titik kantor Anda
 * (buka Google Maps, klik kanan di lokasi kantor, klik angka koordinatnya).
 */
class AbsenSeeder extends Seeder
{
    public function run(): void
    {
        $office = Office::updateOrCreate(
            ['name' => 'Kantor Pusat'],
            [
                'latitude' => 0.4513727640356217,    // <-- ganti
                'longitude' => 101.44798663664022, // <-- ganti
                'radius' => 100,
                'is_active' => true,
            ]
        );

        $this->user('admin@absen.test', 'Admin', 'ADM001', 'admin', $office->id);
        $this->user('pegawai@absen.test', 'Pegawai Contoh', 'PGW001', 'pegawai', $office->id);
    }

    /**
     * forceFill dipakai supaya model User bawaan starter kit tidak perlu diubah.
     * email_verified_at diisi agar akun tidak tertahan di halaman verifikasi email.
     */
    private function user(string $email, string $name, string $nip, string $role, int $officeId): void
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'nip' => $nip,
            'role' => $role,
            'office_id' => $officeId,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ])->save();
    }
}
