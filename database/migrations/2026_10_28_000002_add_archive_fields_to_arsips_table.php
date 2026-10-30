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
        Schema::table('arsips', function (Blueprint $table) {
            $table->string('nomor', 100)->nullable()->unique()->after('id');
            $table->string('nama', 200)->nullable()->after('nomor');
            $table->foreignId('kategori_arsip_id')
                ->nullable()
                ->after('nama')
                ->constrained('kategori_arsips')
                ->restrictOnDelete();
            $table->foreignId('dprd_member_id')
                ->nullable()
                ->after('kategori_arsip_id')
                ->constrained('dprd_members')
                ->nullOnDelete();
            $table->string('jenis', 20)->default('masuk')->after('dprd_member_id');
            $table->string('status', 30)->default('draf')->after('jenis');
            $table->date('tanggal')->nullable()->after('status');
            $table->text('deskripsi')->nullable()->after('tanggal');
            $table->string('file_path')->nullable()->after('deskripsi');
            $table->string('file_name')->nullable()->after('file_path');
            $table->foreignId('uploaded_by')
                ->nullable()
                ->after('file_name')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('verified_by')
                ->nullable()
                ->after('uploaded_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->softDeletes();

            $table->index('status');
            $table->index('jenis');
            $table->index('tanggal');
            $table->index(['dprd_member_id', 'tanggal']);
        });

        DB::statement("ALTER TABLE arsips ADD CONSTRAINT arsips_jenis_check CHECK (jenis IN ('masuk', 'keluar', 'internal'))");
        DB::statement("ALTER TABLE arsips ADD CONSTRAINT arsips_status_check CHECK (status IN ('draf', 'menunggu_verifikasi', 'terverifikasi', 'ditolak'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE arsips DROP CHECK arsips_status_check');
        DB::statement('ALTER TABLE arsips DROP CHECK arsips_jenis_check');

        Schema::table('arsips', function (Blueprint $table) {
            $table->dropForeign(['kategori_arsip_id']);
            $table->dropForeign(['dprd_member_id']);
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['verified_by']);
            $table->dropUnique(['nomor']);
            $table->dropIndex(['status']);
            $table->dropIndex(['jenis']);
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['dprd_member_id', 'tanggal']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'nomor',
                'nama',
                'kategori_arsip_id',
                'dprd_member_id',
                'jenis',
                'status',
                'tanggal',
                'deskripsi',
                'file_path',
                'file_name',
                'uploaded_by',
                'verified_by',
                'verified_at',
            ]);
        });
    }
};
