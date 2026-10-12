<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun awal. Ganti kata sandinya setelah login pertama.
     *   Admin   : admin@kampus.ac.id   / admin123
     *   Asisten : asisten@kampus.ac.id / asisten123
     *   Mahasiswa contoh: 50421001@contoh.ac.id s.d. 50421010@contoh.ac.id, kata sandi = NPM (kelas A, sudah ikut Basis Data)
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@kampus.ac.id'], ['name' => 'Admin', 'password' => 'admin123', 'role' => 'admin']);
        $asisten = User::updateOrCreate(['email' => 'asisten@kampus.ac.id'], ['name' => 'Asisten Demo', 'password' => 'asisten123', 'role' => 'asisten']);

        if ($this->command->confirm('Buat kelas & mahasiswa contoh?', true)) {
            $kelas = Kelas::firstOrCreate(
                ['asisten_id' => $asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data'],
                ['hari' => 'Senin 08.00', 'ruang' => 'Lab 3', 'jumlah_pertemuan' => 8]
            );
            // matkul lain yang bisa dipilih sendiri oleh mahasiswa dari akunnya
            Kelas::firstOrCreate(['asisten_id' => $asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Pemrograman Web'],
                ['hari' => 'Rabu 10.00', 'ruang' => 'Lab 1', 'jumlah_pertemuan' => 8]);
            Kelas::firstOrCreate(['asisten_id' => $asisten->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Basis Data'],
                ['hari' => 'Selasa 08.00', 'ruang' => 'Lab 3', 'jumlah_pertemuan' => 8]);
            $nama = ['Andi Saputra', 'Budi Santoso', 'Citra Lestari', 'Dewi Anggraini', 'Eko Prasetyo',
                'Fitri Handayani', 'Gilang Ramadhan', 'Hana Pertiwi', 'Indra Wijaya', 'Joko Susilo'];
            foreach ($nama as $i => $n) {
                $npm = '504210'.str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                $m = Mahasiswa::firstOrCreate(['npm' => $npm], ['nama' => $n, 'password' => $npm, 'kelas' => 'A',
                    'email' => $npm.'@contoh.ac.id', 'email_verified_at' => now()]);
                $kelas->mahasiswa()->syncWithoutDetaching([$m->id]);
            }
        }
    }
}
