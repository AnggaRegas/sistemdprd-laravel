<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_masyarakat_id')
                ->constrained('laporan_masyarakat', 'id', 'laporan_analysis_laporan_fk')
                ->cascadeOnDelete();
            $table->foreignId('dprd_member_id')
                ->nullable()
                ->constrained('dprd_members', 'id', 'laporan_analysis_member_fk')
                ->nullOnDelete();
            $table->decimal('threshold', 8, 6)->nullable();
            $table->unsignedInteger('max_results')->nullable();
            $table->unsignedInteger('candidate_count')->default(0);
            $table->decimal('highest_similarity', 8, 6)->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('has_detail')->default(false);
            $table->timestamp('analyzed_at');
            $table->string('algorithm_version', 32);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique('laporan_masyarakat_id', 'laporan_analysis_laporan_unique');
            $table->index('dprd_member_id', 'laporan_analysis_member_idx');
            $table->index(['is_duplicate', 'analyzed_at'], 'laporan_analysis_result_idx');
        });

        /*
         * Backfill riwayat lama dari hasil similarity yang sudah tersimpan.
         * Perhitungan lawas tidak diulang: hanya nilai asli yang disimpan,
         * dengan tanda has_detail=false agar halaman tidak mengarang rincian.
         */
        if (! Schema::hasTable('laporan_similarity_results')) {
            return;
        }

        $groups = DB::table('laporan_similarity_results')
            ->selectRaw('laporan_masyarakat_id, MAX(similarity_score) as highest, MAX(checked_at) as last_check, COUNT(*) as total')
            ->groupBy('laporan_masyarakat_id')
            ->get();

        foreach ($groups as $group) {
            $laporan = DB::table('laporan_masyarakat')
                ->where('id', $group->laporan_masyarakat_id)
                ->first(['id', 'dprd_member_id']);

            if (! $laporan) {
                continue;
            }

            DB::table('laporan_analysis_runs')->insert([
                'laporan_masyarakat_id' => $laporan->id,
                'dprd_member_id' => $laporan->dprd_member_id,
                'threshold' => null,
                'max_results' => null,
                'candidate_count' => $group->total,
                'highest_similarity' => $group->highest,
                'is_duplicate' => (int) $group->total > 0,
                'has_detail' => false,
                'analyzed_at' => $group->last_check,
                'algorithm_version' => 'legacy',
                'snapshot' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_analysis_runs');
    }
};
