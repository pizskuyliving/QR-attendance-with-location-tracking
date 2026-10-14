<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hitungan "pesan baru" dijalankan di setiap halaman asisten: cari per kelas yang belum dibaca
        Schema::table('catatan_dosen', function (Blueprint $t) {
            $t->index(['kelas_id', 'dibaca_at']);
        });
    }

    public function down(): void
    {
        Schema::table('catatan_dosen', function (Blueprint $t) {
            $t->dropIndex(['kelas_id', 'dibaca_at']);
        });
    }
};
