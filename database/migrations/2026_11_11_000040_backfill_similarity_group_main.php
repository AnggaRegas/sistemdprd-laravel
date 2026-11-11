<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tetapkan aspirasi utama untuk kelompok lama yang belum memilikinya.
     * Aspirasi utama dipilih dari laporan yang paling dahulu masuk, bukan
     * dari ID. Migration ini hanya melengkapi data dan tidak menghapus
     * kelompok, anggota, maupun hasil perhitungan kemiripan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('laporan_similarity_groups')
            || ! Schema::hasTable('laporan_similarity_group_members')) {
            return;
        }

        DB::table('laporan_similarity_groups')
            ->whereNull('main_laporan_id')
            ->where('status', '<>', 'dibubarkan')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($group): void {
                $mainId = DB::table('laporan_similarity_group_members as members')
                    ->join('laporan_masyarakat as reports', 'reports.id', '=', 'members.laporan_masyarakat_id')
                    ->where('members.laporan_similarity_group_id', $group->id)
                    ->orderBy('reports.created_at')
                    ->orderBy('reports.id')
                    ->value('reports.id');

                if ($mainId === null) {
                    return;
                }

                DB::table('laporan_similarity_groups')
                    ->where('id', $group->id)
                    ->update(['main_laporan_id' => $mainId, 'updated_at' => now()]);

                DB::table('laporan_similarity_group_members')
                    ->where('laporan_similarity_group_id', $group->id)
                    ->update(['peran' => 'anggota', 'updated_at' => now()]);

                DB::table('laporan_similarity_group_members')
                    ->where('laporan_similarity_group_id', $group->id)
                    ->where('laporan_masyarakat_id', $mainId)
                    ->update(['peran' => 'utama', 'updated_at' => now()]);
            });
    }

    public function down(): void
    {
        // Data utama yang sudah ditetapkan tidak dikembalikan.
    }
};
