<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('waktu');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->unsignedInteger('akurasi')->nullable()->after('lng'); // meter
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'akurasi']);
        });
    }
};
