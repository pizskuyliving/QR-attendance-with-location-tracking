<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // catatan/teguran dosen untuk asisten satu kelas; dibaca_at diisi saat asisten membuka kelasnya
        Schema::create('catatan_dosen', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $t->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $t->text('isi');
            $t->timestamp('dibaca_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_dosen');
    }
};
