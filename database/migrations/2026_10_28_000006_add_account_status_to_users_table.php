<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan status akun, kewajiban ganti kata sandi, dan soft delete.
     *
     * Unik lama pada dprd_member_id dihapus karena staf nonaktif atau yang
     * sudah dihapus tetap harus menyimpan riwayat penugasannya. Sebagai
     * gantinya, generated column hanya berisi anggota untuk staf yang masih
     * aktif sehingga satu anggota tetap hanya boleh punya satu staf aktif.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->softDeletes()->after('must_change_password');
        });

        // Indeks biasa dibutuhkan foreign key dprd_member_id sebelum unique
        // lama dilepas. Tanpa ini MySQL dapat menolak penghapusan indeks.
        Schema::table('users', function (Blueprint $table) {
            $table->index('dprd_member_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['dprd_member_id']);
        });

        DB::statement(
            "ALTER TABLE users ADD COLUMN active_dprd_member_id BIGINT UNSIGNED "
            ."GENERATED ALWAYS AS ("
            ."CASE WHEN role = 'staf_pendamping' AND is_active = 1 AND deleted_at IS NULL "
            ."THEN dprd_member_id ELSE NULL END"
            .") STORED"
        );

        DB::statement(
            'CREATE UNIQUE INDEX users_active_member_unique ON users (active_dprd_member_id)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX users_active_member_unique ON users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_dprd_member_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['is_active', 'must_change_password']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['dprd_member_id']);
            $table->unique('dprd_member_id');
        });
    }
};
