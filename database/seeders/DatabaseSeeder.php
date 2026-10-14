<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Matkul;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun awal. Ganti kata sandinya setelah login pertama.
     *   Laboran : admin@kampus.ac.id   / admin123
     *   Asisten : asisten@kampus.ac.id / asisten123
     *   Dosen   : dosen@kampus.ac.id   / dosen123
     * Akun mahasiswa tidak dibuat di sini: mahasiswa mendaftar sendiri lewat /daftar.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@kampus.ac.id'], ['name' => 'Laboran', 'password' => 'admin123', 'role' => 'admin']);
        $asisten = User::updateOrCreate(['email' => 'asisten@kampus.ac.id'], ['name' => 'Asisten Demo', 'password' => 'asisten123', 'role' => 'asisten']);
        $dosen = User::updateOrCreate(['email' => 'dosen@kampus.ac.id'], ['name' => 'Dosen Demo', 'password' => 'dosen123', 'role' => 'dosen']);

        if ($this->command->confirm('Buat matkul & kelas contoh?', true)) {
            // kelas dibuat dosen di dalam mata kuliahnya, lalu asisten dipilih per kelas
            $bd = Matkul::firstOrCreate(['dosen_id' => $dosen->id, 'nama' => 'Basis Data']);
            $web = Matkul::firstOrCreate(['dosen_id' => $dosen->id, 'nama' => 'Pemrograman Web']);
            Kelas::firstOrCreate(
                ['matkul_id' => $bd->id, 'asisten_id' => $asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data'],
                ['hari' => 'Senin 08.00', 'ruang' => 'Lab 3', 'jumlah_pertemuan' => 8]
            );
            // matkul lain yang bisa dipilih sendiri oleh mahasiswa dari akunnya
            Kelas::firstOrCreate(['matkul_id' => $web->id, 'asisten_id' => $asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Pemrograman Web'],
                ['hari' => 'Rabu 10.00', 'ruang' => 'Lab 1', 'jumlah_pertemuan' => 8]);
            Kelas::firstOrCreate(['matkul_id' => $bd->id, 'asisten_id' => $asisten->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Basis Data'],
                ['hari' => 'Selasa 08.00', 'ruang' => 'Lab 3', 'jumlah_pertemuan' => 8]);
        }
    }
}
