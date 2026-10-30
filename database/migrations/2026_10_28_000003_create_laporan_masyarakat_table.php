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
        Schema::create('laporan_masyarakat', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 30)->unique();
            $table->string('pelapor_nama', 150);
            $table->string('pelapor_kontak', 30)->nullable();
            $table->text('pelapor_alamat')->nullable();
            $table->string('ringkasan', 255);
            $table->text('isi')->nullable();
            $table->string('kategori', 100)->nullable();
            $table->foreignId('dprd_member_id')
                ->nullable()
                ->constrained('dprd_members')
                ->nullOnDelete();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status', 30)->default('baru');
            $table->date('tanggal_laporan');
            $table->timestamp('ditutup_at')->nullable();
            $table->text('catatan_penyelesaian')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('tanggal_laporan');
            $table->index('kategori');
            $table->index(['dprd_member_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        DB::statement("ALTER TABLE laporan_masyarakat ADD CONSTRAINT laporan_masyarakat_status_check CHECK (status IN ('baru', 'menunggu_penugasan', 'diproses', 'selesai', 'ditolak'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_masyarakat');
    }
};
