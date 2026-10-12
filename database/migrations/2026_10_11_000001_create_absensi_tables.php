<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->string('npm')->unique();
            $table->string('nama');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asisten_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_kelas');      // A–G (config/absen.php)
            $table->string('mata_kuliah');     // contoh: Basis Data
            $table->string('hari')->nullable();
            $table->string('ruang')->nullable();
            $table->unsignedTinyInteger('jumlah_pertemuan')->default(8);
            $table->timestamps();
        });

        Schema::create('kelas_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['kelas_id', 'mahasiswa_id']);
        });

        Schema::create('sesi_absen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->string('secret', 64);
            $table->timestamp('dibuka_at');
            $table->timestamp('ditutup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->string('status', 1); // H = hadir, I = izin, S = sakit, A = alpa
            $table->string('metode')->default('manual'); // qr | manual | import
            $table->foreignId('sesi_absen_id')->nullable()->constrained('sesi_absen')->nullOnDelete();
            $table->timestamp('waktu')->nullable();
            $table->timestamps();
            $table->unique(['kelas_id', 'mahasiswa_id', 'pertemuan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('sesi_absen');
        Schema::dropIfExists('kelas_mahasiswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('mahasiswa');
    }
};
