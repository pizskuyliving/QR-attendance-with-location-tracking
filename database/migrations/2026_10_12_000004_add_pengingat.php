<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $t) {
            $t->unsignedTinyInteger('min_hadir')->default(75); // persen kehadiran minimal
        });

        // catatan email otomatis yang sudah terkirim, agar tidak dikirim dua kali
        Schema::create('notifikasi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $t->string('kunci', 60); // mis. hadir:3:batas, tugas:12
            $t->timestamp('dikirim_at');
            $t->unique(['mahasiswa_id', 'kunci']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::table('kelas', fn (Blueprint $t) => $t->dropColumn('min_hadir'));
    }
};
