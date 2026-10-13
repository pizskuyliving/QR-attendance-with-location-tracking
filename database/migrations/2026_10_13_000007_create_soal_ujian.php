<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // file soal UTS/UAS yang diunggah asisten; disembunyikan dari mahasiswa sampai dibagikan
        Schema::create('soal_ujian', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->string('jenis', 3); // uts | uas
            $t->string('judul', 150);
            $t->string('path');
            $t->string('nama_file');
            $t->unsignedBigInteger('ukuran')->nullable();
            $t->boolean('tampil_mahasiswa')->default(false);
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['kelas_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal_ujian');
    }
};
