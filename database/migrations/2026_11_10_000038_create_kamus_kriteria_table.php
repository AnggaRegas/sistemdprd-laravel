<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Kriteria dan bobot perhitungan Kamus Acuan (SPK).
         *
         * Setiap baris adalah satu parameter perhitungan yang benar-benar
         * dipakai oleh KamusAcuanService. Nilai disimpan per versi kamus agar
         * hasil pemeriksaan historis tetap dapat direproduksi.
         */
        Schema::create('kamus_kriteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamus_acuan_versi_id')
                ->constrained('kamus_acuan_versi', 'id', 'kamus_kriteria_versi_fk')
                ->cascadeOnDelete();
            $table->string('kode', 50);
            $table->string('nama', 120);
            $table->decimal('bobot', 8, 3)->default(1);
            $table->string('satuan', 30)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['kamus_acuan_versi_id', 'kode'], 'kamus_kriteria_versi_kode_unique');
            $table->index(['kamus_acuan_versi_id', 'urutan'], 'kamus_kriteria_urutan_idx');
        });

        /*
         * Backfill kriteria bawaan untuk versi yang sudah ada. Tanpa ini
         * versi lama tetap berjalan memakai nilai bawaan service, tetapi
         * admin tidak akan melihat baris yang dapat diedit.
         */
        $defaults = [
            ['kode' => 'bobot_kata_kunci', 'nama' => 'Bobot kata kunci', 'bobot' => 1.0, 'satuan' => 'pengali', 'keterangan' => 'Pengali untuk istilah yang berasal dari kolom kata kunci entri kamus.'],
            ['kode' => 'bobot_sinonim', 'nama' => 'Bobot sinonim', 'bobot' => 0.8, 'satuan' => 'pengali', 'keterangan' => 'Pengali untuk istilah yang berasal dari kolom sinonim entri kamus.'],
            ['kode' => 'bobot_frasa', 'nama' => 'Bonus frasa', 'bobot' => 1.25, 'satuan' => 'pengali', 'keterangan' => 'Pengali tambahan bila istilah cocok berupa frasa (lebih dari satu kata).'],
            ['kode' => 'maks_kemunculan', 'nama' => 'Batas kemunculan istilah', 'bobot' => 3, 'satuan' => 'kali', 'keterangan' => 'Jumlah maksimum kemunculan satu istilah yang ikut dihitung per pemeriksaan.'],
            ['kode' => 'skor_minimum', 'nama' => 'Skor minimum sesuai kamus', 'bobot' => 4, 'satuan' => 'skor', 'keterangan' => 'Skor minimal agar satu kandidat tunggal dinyatakan sesuai kamus.'],
            ['kode' => 'panjang_minimum_teks', 'nama' => 'Panjang minimum teks', 'bobot' => 12, 'satuan' => 'karakter', 'keterangan' => 'Teks aspirasi di bawah batas ini dianggap belum jelas dan perlu verifikasi.'],
            ['kode' => 'minimal_jumlah_kata', 'nama' => 'Jumlah minimum kata', 'bobot' => 3, 'satuan' => 'kata', 'keterangan' => 'Jumlah kata minimum agar teks aspirasi dianggap cukup jelas.'],
        ];

        $now = now();

        foreach (\Illuminate\Support\Facades\DB::table('kamus_acuan_versi')->pluck('id') as $versionId) {
            $urutan = 1;

            foreach ($defaults as $default) {
                \Illuminate\Support\Facades\DB::table('kamus_kriteria')->insert([
                    'kamus_acuan_versi_id' => $versionId,
                    'kode' => $default['kode'],
                    'nama' => $default['nama'],
                    'bobot' => $default['bobot'],
                    'satuan' => $default['satuan'],
                    'keterangan' => $default['keterangan'],
                    'is_active' => true,
                    'urutan' => $urutan++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kamus_kriteria');
    }
};
