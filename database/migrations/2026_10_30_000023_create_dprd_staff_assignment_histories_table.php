<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat penugasan staf tidak dihapus saat staf dipindahkan. Satu baris
     * mencatat staf, anggota tujuan (null saat dilepas), anggota sebelumnya,
     * administrator yang mengubah, dan sumber perubahan.
     */
    public function up(): void
    {
        Schema::create('dprd_staff_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dprd_staff_id')
                ->constrained('dprd_staffs')
                ->cascadeOnDelete();
            $table->foreignId('dprd_member_id')
                ->nullable()
                ->constrained('dprd_members')
                ->nullOnDelete();
            $table->foreignId('previous_member_id')
                ->nullable()
                ->constrained('dprd_members')
                ->nullOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('source', 30)->default('manual');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['dprd_staff_id', 'created_at']);
            $table->index(['dprd_member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dprd_staff_assignment_histories');
    }
};
