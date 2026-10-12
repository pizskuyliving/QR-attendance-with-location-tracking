<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->string('kelas')->nullable()->after('nama');          // kelas A–G yang dipilih saat daftar
            $table->string('email')->nullable()->unique()->after('kelas');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('kode_verifikasi')->nullable()->after('email_verified_at'); // hash dari kode 6 digit
            $table->timestamp('kode_kedaluwarsa')->nullable()->after('kode_verifikasi');
            $table->unsignedTinyInteger('percobaan_kode')->default(0)->after('kode_kedaluwarsa');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['kelas', 'email', 'email_verified_at', 'kode_verifikasi', 'kode_kedaluwarsa', 'percobaan_kode']);
        });
    }
};
