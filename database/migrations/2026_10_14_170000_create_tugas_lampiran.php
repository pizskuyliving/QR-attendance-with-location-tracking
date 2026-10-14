<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lampiran dari asisten untuk satu tugas: banyak file dan/atau tautan
        Schema::create('tugas_lampiran', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $t->string('judul', 500);
            $t->string('path')->nullable();       // file: disimpan tanpa ekstensi, nama asli di nama_file
            $t->string('nama_file')->nullable();
            $t->unsignedBigInteger('ukuran')->nullable();
            $t->string('url', 500)->nullable();   // tautan
            $t->timestamps();
            $t->index('tugas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_lampiran');
    }
};
