<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // UTS dan UAS sebagai komponen nilai akhir dengan bobotnya sendiri
        Schema::table('kelas', function (Blueprint $t) {
            $t->unsignedTinyInteger('bobot_uts')->default(0);
            $t->unsignedTinyInteger('bobot_uas')->default(0);
        });
        // nilai UTS & UAS per mahasiswa per kelas
        Schema::table('kelas_mahasiswa', function (Blueprint $t) {
            $t->decimal('nilai_uts', 5, 2)->nullable();
            $t->decimal('nilai_uas', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('kelas_mahasiswa', function (Blueprint $t) {
            $t->dropColumn(['nilai_uts', 'nilai_uas']);
        });
        Schema::table('kelas', function (Blueprint $t) {
            $t->dropColumn(['bobot_uts', 'bobot_uas']);
        });
    }
};
