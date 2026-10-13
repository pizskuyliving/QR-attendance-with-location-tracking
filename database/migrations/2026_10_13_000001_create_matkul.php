<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // mata kuliah milik dosen; kelas asisten ditautkan lewat kode undangan
        Schema::create('matkul', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $t->string('kode', 20)->nullable();      // kode matkul kampus, mis. IF231
            $t->string('nama', 100);
            $t->string('semester', 30)->nullable();  // mis. Ganjil 2026/2027
            $t->string('kode_undangan', 8)->unique();
            $t->timestamps();
        });

        Schema::table('kelas', function (Blueprint $t) {
            $t->foreignId('matkul_id')->nullable()->after('asisten_id')->constrained('matkul')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', fn (Blueprint $t) => $t->dropConstrainedForeignId('matkul_id'));
        Schema::dropIfExists('matkul');
    }
};
