<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->dateTime('check_in_at');
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->unsignedInteger('check_in_distance')->nullable(); // meter dari kantor

            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->unsignedInteger('check_out_distance')->nullable();

            $table->string('status')->default('hadir'); // hadir | terlambat
            $table->timestamps();

            // Satu baris absen per pegawai per hari.
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
