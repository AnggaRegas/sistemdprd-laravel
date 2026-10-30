<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghubungkan akun login staf dengan data referensi staf pendamping.
     * Kolom ini tidak mengubah dprd_member_id yang tetap menjadi sumber
     * pembatasan akses data di backend.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('dprd_staff_id')
                ->nullable()
                ->unique()
                ->after('dprd_member_id')
                ->constrained('dprd_staffs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dprd_staff_id');
        });
    }
};
