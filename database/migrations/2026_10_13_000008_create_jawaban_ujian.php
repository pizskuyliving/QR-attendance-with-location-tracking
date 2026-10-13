<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // batas unggah jawaban UTS/UAS; null = pengumpulan jawaban ditutup
        Schema::table('kelas', function (Blueprint $t) {
            $t->dateTime('tenggat_uts')->nullable();
            $t->dateTime('tenggat_uas')->nullable();
        });
        // jawaban UTS/UAS mahasiswa; nilainya tetap di kelas_mahasiswa.nilai_uts/nilai_uas
        Schema::create('jawaban_ujian', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $t->string('jenis', 3); // uts | uas
            $t->string('path');
            $t->string('nama_file');
            $t->unsignedBigInteger('ukuran')->nullable();
            $t->string('catatan', 500)->nullable();
            $t->text('komentar')->nullable();
            $t->dateTime('dikumpulkan_at');
            $t->timestamps();
            $t->unique(['kelas_id', 'mahasiswa_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_ujian');
        Schema::table('kelas', function (Blueprint $t) {
            $t->dropColumn(['tenggat_uts', 'tenggat_uas']);
        });
    }
};
