<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // aktif = boleh login; menunggu = daftar sendiri, belum disetujui admin; ditolak
            $table->string('status', 10)->default('aktif');
            $table->string('keterangan', 255)->nullable(); // diisi saat daftar, mis. matkul yang diampu
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['status', 'keterangan']));
    }
};
