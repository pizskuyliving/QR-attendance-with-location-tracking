<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique()->after('id');
            $table->string('role')->default('pegawai')->after('password'); // admin | pegawai
            $table->foreignId('office_id')->nullable()->after('role')
                ->constrained('offices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn(['nip', 'role']);
        });
    }
};
