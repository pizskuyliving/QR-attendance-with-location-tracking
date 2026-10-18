<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // alasan jawaban terkirim tanpa tombol Kirim, mis. "pindah_tab" (mahasiswa meninggalkan halaman kuis)
        Schema::table('kuis_jawaban', fn (Blueprint $t) => $t->string('kirim_otomatis', 20)->nullable()->after('selesai_at'));
    }

    public function down(): void
    {
        Schema::table('kuis_jawaban', fn (Blueprint $t) => $t->dropColumn('kirim_otomatis'));
    }
};
