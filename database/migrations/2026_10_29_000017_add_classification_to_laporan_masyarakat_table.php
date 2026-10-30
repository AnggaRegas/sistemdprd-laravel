<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekomendasi otomatis dan keputusan final Administrator disimpan pada
     * kolom yang terpisah agar hasil rule-based tetap dapat diaudit.
     */
    public function up(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->foreignId('klasifikasi_komisi_id')
                ->nullable()
                ->after('kategori')
                ->constrained('dprd_commissions')
                ->nullOnDelete();
            $table->decimal('klasifikasi_skor', 6, 2)->nullable()->after('klasifikasi_komisi_id');
            $table->decimal('klasifikasi_skor_kedua', 6, 2)->nullable()->after('klasifikasi_skor');
            $table->json('klasifikasi_keywords')->nullable()->after('klasifikasi_skor_kedua');
            $table->json('klasifikasi_skor_detail')->nullable()->after('klasifikasi_keywords');
            $table->string('klasifikasi_metode', 60)->nullable()->after('klasifikasi_skor_detail');
            $table->string('klasifikasi_status', 30)->nullable()->after('klasifikasi_metode');
            $table->text('klasifikasi_alasan')->nullable()->after('klasifikasi_status');
            $table->timestamp('klasifikasi_dianalisis_at')->nullable()->after('klasifikasi_alasan');

            $table->foreignId('komisi_final_id')
                ->nullable()
                ->after('klasifikasi_dianalisis_at')
                ->constrained('dprd_commissions')
                ->nullOnDelete();
            $table->foreignId('komisi_final_ditetapkan_oleh')
                ->nullable()
                ->after('komisi_final_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('komisi_final_ditetapkan_at')->nullable()->after('komisi_final_ditetapkan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('komisi_final_ditetapkan_oleh');
            $table->dropConstrainedForeignId('komisi_final_id');
            $table->dropConstrainedForeignId('klasifikasi_komisi_id');
            $table->dropColumn([
                'klasifikasi_skor',
                'klasifikasi_skor_kedua',
                'klasifikasi_keywords',
                'klasifikasi_skor_detail',
                'klasifikasi_metode',
                'klasifikasi_status',
                'klasifikasi_alasan',
                'klasifikasi_dianalisis_at',
                'komisi_final_ditetapkan_at',
            ]);
        });
    }
};
