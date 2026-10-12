<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('dua_langkah_rahasia')->nullable();          // terenkripsi APP_KEY
            $table->timestamp('dua_langkah_aktif_at')->nullable();     // null = 2FA belum aktif
            $table->text('dua_langkah_cadangan')->nullable();          // hash kode cadangan, terenkripsi
            $table->unsignedBigInteger('dua_langkah_terakhir')->nullable(); // langkah waktu terakhir dipakai (anti pakai ulang)
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['dua_langkah_rahasia', 'dua_langkah_aktif_at', 'dua_langkah_cadangan', 'dua_langkah_terakhir']));
    }
};
