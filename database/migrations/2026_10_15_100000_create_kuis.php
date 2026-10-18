<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // kuis refleksi di akhir pertemuan: maks. satu per pertemuan, 5 soal pilihan A/B/C
        Schema::create('kuis', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->unsignedSmallInteger('pertemuan');
            $t->unsignedSmallInteger('durasi_menit')->default(10);
            $t->timestamp('dibuka_at')->nullable();   // null = masih draf
            $t->timestamp('ditutup_at')->nullable();  // batas akhir menjawab
            $t->timestamps();
            $t->unique(['kelas_id', 'pertemuan']);
        });

        Schema::create('kuis_soal', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $t->unsignedTinyInteger('urutan');
            $t->text('pertanyaan');
            $t->string('pilihan_a', 500);
            $t->string('pilihan_b', 500);
            $t->string('pilihan_c', 500);
            $t->char('kunci', 1); // a | b | c
            $t->timestamps();
            $t->index('kuis_id');
        });

        // satu baris per mahasiswa per kuis; jawaban {soal_id: a|b|c} disimpan otomatis tiap memilih
        Schema::create('kuis_jawaban', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $t->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $t->json('jawaban')->nullable();
            $t->unsignedTinyInteger('benar')->default(0);
            $t->decimal('nilai', 5, 2)->default(0);
            $t->timestamp('mulai_at')->nullable();
            $t->timestamp('selesai_at')->nullable();
            $t->timestamps();
            $t->unique(['kuis_id', 'mahasiswa_id']);
            $t->index('mahasiswa_id');
        });

        Schema::table('kelas', fn (Blueprint $t) => $t->unsignedTinyInteger('bobot_kuis')->default(0)->after('bobot_tugas'));
    }

    public function down(): void
    {
        Schema::table('kelas', fn (Blueprint $t) => $t->dropColumn('bobot_kuis'));
        Schema::dropIfExists('kuis_jawaban');
        Schema::dropIfExists('kuis_soal');
        Schema::dropIfExists('kuis');
    }
};
