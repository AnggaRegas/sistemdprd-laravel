<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu anggota hanya boleh memiliki satu staf pendamping. Relasi memakai
     * foreign key ke data referensi staf, bukan input teks bebas.
     */
    public function up(): void
    {
        Schema::table('dprd_members', function (Blueprint $table) {
            $table->foreignId('staff_id')
                ->nullable()
                ->unique()
                ->after('dprd_region_id')
                ->constrained('dprd_staffs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dprd_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
        });
    }
};
