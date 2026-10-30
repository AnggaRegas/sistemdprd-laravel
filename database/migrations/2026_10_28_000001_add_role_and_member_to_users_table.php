<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->nullable()->after('email');
            $table->foreignId('dprd_member_id')
                ->nullable()
                ->unique()
                ->after('role')
                ->constrained('dprd_members')
                ->restrictOnDelete();

            $table->index('role');
        });

        // Akun yang sudah ada sebelum Tahap 2 adalah akun pengelola (superadmin).
        // Nilainya hanya diisi jika masih kosong agar tidak menimpa perubahan manual.
        DB::table('users')->whereNull('role')->update(['role' => 'administrator']);

        DB::statement('ALTER TABLE users MODIFY role VARCHAR(32) NOT NULL');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('administrator', 'staf_pendamping'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_member_check CHECK ((role = 'administrator' AND dprd_member_id IS NULL) OR (role = 'staf_pendamping' AND dprd_member_id IS NOT NULL))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CHECK users_role_member_check');
        DB::statement('ALTER TABLE users DROP CHECK users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['dprd_member_id']);
            $table->dropUnique(['dprd_member_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'dprd_member_id']);
        });
    }
};
