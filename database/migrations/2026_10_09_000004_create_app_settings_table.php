<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan aplikasi (jam masuk, jam pulang, dst.) yang bisa diubah admin.
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Tandai absen pulang yang dilakukan sebelum jam pulang.
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('early_leave')->default(false)->after('check_out_distance');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('early_leave');
        });

        Schema::dropIfExists('app_settings');
    }
};
