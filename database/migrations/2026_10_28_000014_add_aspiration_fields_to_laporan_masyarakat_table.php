<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tambahan untuk aspirasi publik. Data lama tidak diubah dan
     * migration ini tidak menghapus tabel atau baris yang sudah ada.
     */
    public function up(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->string('kecamatan', 100)->nullable()->after('kategori');
            $table->string('lampiran_path', 255)->nullable()->after('kecamatan');
            $table->string('lampiran_nama', 255)->nullable()->after('lampiran_path');
            $table->string('lampiran_mime', 100)->nullable()->after('lampiran_nama');
            $table->unsignedBigInteger('lampiran_size')->nullable()->after('lampiran_mime');

            $table->index('kecamatan');
        });

        /*
         * Kontak pelapor dapat berupa nomor telepon atau email. Kolom lama
         * hanya 30 karakter sehingga alamat email yang wajar tidak muat.
         * Perubahan ini hanya memperlebar kolom, bukan menghapus data.
         */
        DB::statement('ALTER TABLE laporan_masyarakat MODIFY pelapor_kontak VARCHAR(150) NULL');

        /*
         * Status "ditugaskan" dibutuhkan untuk alur penugasan eksplisit oleh
         * administrator. Constraint lama tidak mengizinkannya.
         */
        DB::statement('ALTER TABLE laporan_masyarakat DROP CHECK laporan_masyarakat_status_check');
        DB::statement("ALTER TABLE laporan_masyarakat ADD CONSTRAINT laporan_masyarakat_status_check CHECK (status IN ('baru', 'menunggu_penugasan', 'ditugaskan', 'diproses', 'selesai', 'ditolak'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
         * Kembalikan laporan berstatus ditugaskan ke status yang masih
         * diizinkan oleh constraint lama sebelum constraint dipasang ulang.
         */
        DB::table('laporan_masyarakat')
            ->where('status', 'ditugaskan')
            ->update(['status' => 'diproses']);

        DB::statement('ALTER TABLE laporan_masyarakat DROP CHECK laporan_masyarakat_status_check');
        DB::statement("ALTER TABLE laporan_masyarakat ADD CONSTRAINT laporan_masyarakat_status_check CHECK (status IN ('baru', 'menunggu_penugasan', 'diproses', 'selesai', 'ditolak'))");

        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropIndex(['kecamatan']);
            $table->dropColumn([
                'kecamatan',
                'lampiran_path',
                'lampiran_nama',
                'lampiran_mime',
                'lampiran_size',
            ]);
        });
    }
};
