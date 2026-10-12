<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // radius kampus: scan di luar radius dari titik ini ditolak (kosong = tidak dibatasi)
        Schema::table('kelas', function (Blueprint $table) {
            $table->decimal('lokasi_lat', 10, 7)->nullable();
            $table->decimal('lokasi_lng', 10, 7)->nullable();
            $table->unsignedInteger('radius')->nullable(); // meter
        });

        // satu akun hanya untuk satu HP
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->string('perangkat', 64)->nullable()->unique(); // sha256 token cookie perangkat
            $table->timestamp('perangkat_terikat_at')->nullable();
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->unsignedInteger('jarak')->nullable();   // meter dari titik kelas
            $table->string('perangkat', 64)->nullable();
            $table->json('tanda')->nullable();              // daftar alasan scan dicurigai
        });

        // jejak perubahan oleh asisten
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('aksi', 40);
            $table->text('keterangan');
            $table->json('data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['kelas_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
        Schema::table('absensi', fn (Blueprint $t) => $t->dropColumn(['jarak', 'perangkat', 'tanda']));
        Schema::table('mahasiswa', function (Blueprint $t) {
            $t->dropUnique(['perangkat']);
            $t->dropColumn(['perangkat', 'perangkat_terikat_at']);
        });
        Schema::table('kelas', fn (Blueprint $t) => $t->dropColumn(['lokasi_lat', 'lokasi_lng', 'radius']));
    }
};
