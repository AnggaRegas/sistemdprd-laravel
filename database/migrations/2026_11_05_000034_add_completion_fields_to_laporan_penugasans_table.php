<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status penugasan staf dipisahkan dari status aspirasi masyarakat.
     * Kolom hasil, bukti, dan waktu penyelesaian dipakai untuk monitoring
     * Superadmin tanpa mengubah status laporan secara otomatis.
     */
    public function up(): void
    {
        Schema::table('laporan_penugasans', function (Blueprint $table) {
            $table->string('status', 40)
                ->default('ditugaskan')
                ->after('catatan');
            $table->timestamp('started_at')->nullable()->after('status');
            $table->text('hasil_tindak_lanjut')->nullable()->after('started_at');
            $table->text('keterangan_tambahan')->nullable()->after('hasil_tindak_lanjut');
            $table->string('bukti_path')->nullable()->after('keterangan_tambahan');
            $table->string('bukti_nama')->nullable()->after('bukti_path');
            $table->string('bukti_mime', 150)->nullable()->after('bukti_nama');
            $table->unsignedBigInteger('bukti_size')->nullable()->after('bukti_mime');
            $table->timestamp('completed_at')->nullable()->after('bukti_size');
            $table->foreignId('completed_by')
                ->nullable()
                ->after('completed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('completed_by');
            $table->foreignId('verified_by')
                ->nullable()
                ->after('verified_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable()->after('verified_by');

            $table->index('status');
            $table->index(['laporan_masyarakat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('laporan_penugasans', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['laporan_masyarakat_id', 'status']);
            $table->dropColumn([
                'status',
                'started_at',
                'hasil_tindak_lanjut',
                'keterangan_tambahan',
                'bukti_path',
                'bukti_nama',
                'bukti_mime',
                'bukti_size',
                'completed_at',
                'completed_by',
                'verified_at',
                'verified_by',
                'catatan_verifikasi',
            ]);
        });
    }
};
