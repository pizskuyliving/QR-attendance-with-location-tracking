<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // satu kelas boleh diajar maksimal dua asisten (Asisten 1 & Asisten 2) dengan akses yang sama
        Schema::table('kelas', function (Blueprint $t) {
            $t->foreignId('asisten2_id')->nullable()->after('asisten_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('asisten2_id');
        });
    }
};
