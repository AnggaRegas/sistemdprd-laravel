<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bersihkan data duplikat riwayat sebelum perbaikan unik diterapkan.
     *
     * Bila terdapat baris ganda pada tabel laporan_similarity_group_members
     * untuk laporan_masyarakat_id yang sama, simpan baris terbaru dan hapus
     * baris duplikat yang lebih lama agar database kembali bersih dan
     * memenuhi constraint unik.
     */
    public function up(): void
    {
        if (! Schema::hasTable('laporan_similarity_group_members')) {
            return;
        }

        $duplicateIds = DB::table('laporan_similarity_group_members')
            ->select('laporan_masyarakat_id')
            ->groupBy('laporan_masyarakat_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('laporan_masyarakat_id');

        foreach ($duplicateIds as $reportId) {
            $latestId = DB::table('laporan_similarity_group_members')
                ->where('laporan_masyarakat_id', $reportId)
                ->orderByDesc('decided_at')
                ->orderByDesc('id')
                ->value('id');

            if ($latestId) {
                DB::table('laporan_similarity_group_members')
                    ->where('laporan_masyarakat_id', $reportId)
                    ->where('id', '<>', $latestId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Migrasi pembersihan data tidak dapat dibatalkan karena baris duplikat
        // yang dihapus adalah anomali data.
    }
};
