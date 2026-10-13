<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // kelas kini dibuat dosen dan asistennya dipilih dosen; kelas boleh belum punya asisten,
        // dan menghapus akun asisten tidak lagi menghapus kelas di matkul dosen
        Schema::table('kelas', function (Blueprint $t) {
            $t->dropForeign(['asisten_id']);
        });
        Schema::table('kelas', function (Blueprint $t) {
            $t->unsignedBigInteger('asisten_id')->nullable()->change();
            $t->foreign('asisten_id')->references('id')->on('users')->nullOnDelete();
        });

        // asisten tidak lagi bergabung dengan kode; dosen memilih langsung
        Schema::table('matkul', function (Blueprint $t) {
            $t->dropUnique(['kode_undangan']);
        });
        Schema::table('matkul', function (Blueprint $t) {
            $t->dropColumn('kode_undangan');
        });
    }

    public function down(): void
    {
        Schema::table('matkul', function (Blueprint $t) {
            $t->string('kode_undangan', 8)->nullable()->unique();
        });
        Schema::table('kelas', function (Blueprint $t) {
            $t->dropForeign(['asisten_id']);
        });
        Schema::table('kelas', function (Blueprint $t) {
            $t->unsignedBigInteger('asisten_id')->nullable(false)->change();
            $t->foreign('asisten_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
