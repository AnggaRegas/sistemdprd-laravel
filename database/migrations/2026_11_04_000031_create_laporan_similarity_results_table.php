<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_similarity_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_masyarakat_id')
                ->constrained('laporan_masyarakat', 'id', 'laporan_similarity_laporan_fk')
                ->cascadeOnDelete();
            $table->foreignId('compared_laporan_masyarakat_id')
                ->constrained('laporan_masyarakat', 'id', 'laporan_similarity_compared_fk')
                ->cascadeOnDelete();
            $table->decimal('similarity_score', 8, 6);
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->unique([
                'laporan_masyarakat_id',
                'compared_laporan_masyarakat_id',
            ], 'laporan_similarity_pair_unique');
            $table->index(['laporan_masyarakat_id', 'similarity_score'], 'laporan_similarity_main_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_similarity_results');
    }
};
