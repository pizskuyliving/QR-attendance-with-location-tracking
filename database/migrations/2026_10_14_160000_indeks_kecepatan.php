<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk query yang paling sering jalan. PostgreSQL & SQLite tidak otomatis membuat indeks
 * untuk foreign key, jadi pencarian "kelas milik asisten X" atau "absen milik mahasiswa Y" sebelumnya
 * membaca seluruh tabel.
 */
return new class extends Migration
{
    private const INDEKS = [
        'kelas' => [['asisten_id'], ['asisten2_id'], ['matkul_id']],
        'matkul' => [['dosen_id']],
        'kelas_mahasiswa' => [['mahasiswa_id']],              // daftar matkul seorang mahasiswa
        'absensi' => [['mahasiswa_id'], ['kelas_id', 'pertemuan']], // riwayat mahasiswa; isi/akhiri sesi per pertemuan
        'sesi_absen' => [['kelas_id', 'ditutup_at']],         // sesi aktif tiap kelas
        'tugas' => [['kelas_id', 'tenggat']],
        'pengumpulan' => [['mahasiswa_id']],
        'jawaban_ujian' => [['mahasiswa_id']],
        'catatan_dosen' => [['dosen_id']],
        'log_aktivitas' => [['user_id']],
    ];

    public function up(): void
    {
        foreach (self::INDEKS as $tabel => $daftar) {
            Schema::table($tabel, function (Blueprint $t) use ($daftar) {
                foreach ($daftar as $kolom) {
                    $t->index($kolom);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEKS as $tabel => $daftar) {
            Schema::table($tabel, function (Blueprint $t) use ($daftar) {
                foreach ($daftar as $kolom) {
                    $t->dropIndex($kolom);
                }
            });
        }
    }
};
