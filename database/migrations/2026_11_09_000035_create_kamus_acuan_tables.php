<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kamus Acuan disimpan terpisah dari konfigurasi komisi agar versi
     * tahunan, riwayat perubahan, dan hasil pemeriksaan historis tidak
     * saling menimpa. Struktur kata kunci mengikuti format data komisi yang
     * sudah ada: daftar {term, weight}.
     */
    public function up(): void
    {
        Schema::create('kamus_acuan_versi', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->string('nama', 150);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('ditetapkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditetapkan_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'tahun']);
        });

        Schema::create('kamus_acuan_entri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamus_acuan_versi_id')
                ->constrained('kamus_acuan_versi')
                ->restrictOnDelete();
            $table->foreignId('dprd_commission_id')
                ->nullable()
                ->constrained('dprd_commissions')
                ->nullOnDelete();
            $table->string('kode', 50);
            $table->string('klasifikasi', 180);
            $table->text('uraian')->nullable();
            $table->json('kata_kunci')->nullable();
            $table->json('sinonim')->nullable();
            $table->text('kriteria')->nullable();
            $table->string('prioritas', 100)->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kamus_acuan_versi_id', 'is_active'], 'kamus_entri_versi_active_index');
            $table->index('klasifikasi');
        });

        /*
         * Kode entri unik per versi hanya untuk baris yang belum dihapus.
         * Entri lama tetap tersimpan sebagai riwayat, sehingga kode yang sama
         * boleh dipakai kembali setelah entri sebelumnya dihapus lunak.
         */
        DB::statement(
            'ALTER TABLE kamus_acuan_entri ADD COLUMN active_kode VARCHAR(50) '
            ."GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN kode ELSE NULL END) STORED"
        );

        DB::statement(
            'CREATE UNIQUE INDEX kamus_entri_versi_active_kode_unique '
            .'ON kamus_acuan_entri (kamus_acuan_versi_id, active_kode)'
        );

        Schema::create('kamus_acuan_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamus_acuan_versi_id')
                ->nullable()
                ->constrained('kamus_acuan_versi')
                ->nullOnDelete();
            $table->foreignId('kamus_acuan_entri_id')
                ->nullable()
                ->constrained('kamus_acuan_entri')
                ->nullOnDelete();
            $table->string('aksi', 40);
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kamus_acuan_entri_id', 'id'], 'kamus_riwayat_entri_index');
            $table->index(['kamus_acuan_versi_id', 'id'], 'kamus_riwayat_versi_index');
        });

        Schema::create('laporan_kamus_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_masyarakat_id')
                ->constrained('laporan_masyarakat')
                ->cascadeOnDelete();
            $table->foreignId('kamus_acuan_versi_id')
                ->nullable()
                ->constrained('kamus_acuan_versi')
                ->nullOnDelete();
            $table->unsignedSmallInteger('tahun_kamus')->nullable();
            $table->string('status', 30);
            $table->string('metode', 60);
            $table->mediumText('teks_diperiksa')->nullable();
            $table->text('alasan')->nullable();
            $table->json('kandidat')->nullable();
            $table->foreignId('entri_utama_id')
                ->nullable()
                ->constrained('kamus_acuan_entri')
                ->nullOnDelete();
            $table->json('entri_utama_snapshot')->nullable();
            $table->string('prioritas_rekomendasi', 100)->nullable();
            $table->unsignedSmallInteger('jumlah_kandidat')->default(0);
            $table->timestamp('diperiksa_at');
            $table->foreignId('diperiksa_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['laporan_masyarakat_id', 'id'], 'laporan_kamus_hasil_laporan_index');
            $table->index('status');
            $table->index('tahun_kamus');
        });

        Schema::create('laporan_kamus_verifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_masyarakat_id')
                ->constrained('laporan_masyarakat')
                ->cascadeOnDelete();
            $table->foreignId('laporan_kamus_hasil_id')
                ->nullable()
                ->constrained('laporan_kamus_hasil')
                ->nullOnDelete();
            $table->string('status_sebelum', 30)->nullable();
            $table->string('status_sesudah', 30);
            $table->foreignId('entri_kamus_id')
                ->nullable()
                ->constrained('kamus_acuan_entri')
                ->nullOnDelete();
            $table->json('entri_snapshot')->nullable();
            $table->string('keputusan', 40);
            $table->text('catatan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at');
            $table->timestamps();

            $table->index(['laporan_masyarakat_id', 'id'], 'laporan_kamus_verifikasi_laporan_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kamus_verifikasi');
        Schema::dropIfExists('laporan_kamus_hasil');
        Schema::dropIfExists('kamus_acuan_riwayat');
        Schema::dropIfExists('kamus_acuan_entri');
        Schema::dropIfExists('kamus_acuan_versi');
    }
};
