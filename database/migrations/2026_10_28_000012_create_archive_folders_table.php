<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Folder adalah pengelompokan dokumen di dalam satu tahun anggaran.
     * Nama folder unik per tahun agar tidak membingungkan pengguna.
     */
    public function up(): void
    {
        Schema::create('archive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_year_id')
                ->constrained('archive_years')
                ->cascadeOnDelete();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->unique(['archive_year_id', 'nama'], 'archive_folders_year_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_folders');
    }
};
