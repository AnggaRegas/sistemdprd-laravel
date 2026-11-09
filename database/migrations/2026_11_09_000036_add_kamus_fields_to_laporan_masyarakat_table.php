<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom ringkas pada laporan hanya dipakai untuk filter dan badge daftar.
     * Hasil otomatis lengkap tetap disimpan pada laporan_kamus_hasil dan
     * keputusan manual pada laporan_kamus_verifikasi.
     */
    public function up(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->string('kamus_status', 30)->nullable()->after('klasifikasi_dianalisis_at');
            $table->string('kamus_status_otomatis', 30)->nullable()->after('kamus_status');
            $table->foreignId('kamus_hasil_id')
                ->nullable()
                ->after('kamus_status_otomatis')
                ->constrained('laporan_kamus_hasil')
                ->nullOnDelete();
            $table->foreignId('kamus_entri_id')
                ->nullable()
                ->after('kamus_hasil_id')
                ->constrained('kamus_acuan_entri')
                ->nullOnDelete();
            $table->foreignId('kamus_versi_id')
                ->nullable()
                ->after('kamus_entri_id')
                ->constrained('kamus_acuan_versi')
                ->nullOnDelete();
            $table->unsignedSmallInteger('kamus_tahun')->nullable()->after('kamus_versi_id');
            $table->string('kamus_prioritas', 100)->nullable()->after('kamus_tahun');
            $table->timestamp('kamus_diperiksa_at')->nullable()->after('kamus_prioritas');
            $table->foreignId('kamus_diverifikasi_oleh')
                ->nullable()
                ->after('kamus_diperiksa_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('kamus_diverifikasi_at')->nullable()->after('kamus_diverifikasi_oleh');
            $table->text('kamus_catatan_verifikasi')->nullable()->after('kamus_diverifikasi_at');

            $table->index('kamus_status', 'laporan_kamus_status_index');
            $table->index('kamus_tahun', 'laporan_kamus_tahun_index');
            $table->index('kamus_prioritas', 'laporan_kamus_prioritas_index');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kamus_hasil_id');
            $table->dropConstrainedForeignId('kamus_entri_id');
            $table->dropConstrainedForeignId('kamus_versi_id');
            $table->dropConstrainedForeignId('kamus_diverifikasi_oleh');
            $table->dropColumn([
                'kamus_status',
                'kamus_status_otomatis',
                'kamus_tahun',
                'kamus_prioritas',
                'kamus_diperiksa_at',
                'kamus_diverifikasi_at',
                'kamus_catatan_verifikasi',
            ]);
        });
    }
};
