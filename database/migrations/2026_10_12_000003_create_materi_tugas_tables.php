<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // materi per pertemuan: file (PPT/PDF/...) atau tautan
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->string('judul', 150);
            $table->string('path')->nullable();        // storage/app/private/...
            $table->string('nama_file')->nullable();
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kelas_id', 'pertemuan']);
        });

        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan');
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->dateTime('tenggat');
            $table->boolean('terima_terlambat')->default(true);
            $table->timestamps();
        });

        Schema::create('pengumpulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_file');
            $table->unsignedBigInteger('ukuran');
            $table->string('catatan', 500)->nullable();
            $table->dateTime('dikumpulkan_at');
            $table->boolean('terlambat')->default(false);
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('komentar')->nullable();
            $table->dateTime('dinilai_at')->nullable();
            $table->timestamps();
            $table->unique(['tugas_id', 'mahasiswa_id']);
        });

        // bobot nilai akhir (persen); sisanya otomatis
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedTinyInteger('bobot_hadir')->default(20);
            $table->unsignedTinyInteger('bobot_tugas')->default(80);
        });
    }

    public function down(): void
    {
        Schema::table('kelas', fn (Blueprint $t) => $t->dropColumn(['bobot_hadir', 'bobot_tugas']));
        Schema::dropIfExists('pengumpulan');
        Schema::dropIfExists('tugas');
        Schema::dropIfExists('materi');
    }
};
