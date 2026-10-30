<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahun anggaran selalu melekat pada satu anggota dan satu kategori.
     * Kombinasi anggota + kategori + tahun dibuat unik agar tidak ada
     * tahun ganda pada hierarki arsip yang sama.
     */
    public function up(): void
    {
        Schema::create('archive_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dprd_member_id')
                ->constrained('dprd_members')
                ->cascadeOnDelete();
            $table->foreignId('kategori_arsip_id')
                ->constrained('kategori_arsips')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->timestamps();

            $table->unique(
                ['dprd_member_id', 'kategori_arsip_id', 'tahun'],
                'archive_years_member_category_year_unique'
            );
            $table->index(['kategori_arsip_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_years');
    }
};
