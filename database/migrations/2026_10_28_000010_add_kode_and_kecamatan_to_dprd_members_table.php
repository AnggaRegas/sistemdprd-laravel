<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode anggota dipakai sebagai identitas resmi (DWN-01, DWN-02, ...).
     * Unik hanya untuk anggota yang belum di-soft delete agar kode dapat
     * dipakai kembali setelah data lama benar-benar dinonaktifkan, tanpa
     * menghilangkan riwayat soft delete.
     */
    public function up(): void
    {
        Schema::table('dprd_members', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->after('id');
            $table->string('kecamatan', 100)->nullable()->after('dapil');
            $table->index('kecamatan');
        });

        DB::statement(
            'ALTER TABLE dprd_members ADD COLUMN active_kode VARCHAR(20) '
            .'GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN kode ELSE NULL END) STORED'
        );

        DB::statement(
            'CREATE UNIQUE INDEX dprd_members_active_kode_unique '
            .'ON dprd_members (active_kode)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX dprd_members_active_kode_unique ON dprd_members');
        DB::statement('ALTER TABLE dprd_members DROP COLUMN active_kode');

        Schema::table('dprd_members', function (Blueprint $table) {
            $table->dropIndex(['kecamatan']);
            $table->dropColumn(['kode', 'kecamatan']);
        });
    }
};
