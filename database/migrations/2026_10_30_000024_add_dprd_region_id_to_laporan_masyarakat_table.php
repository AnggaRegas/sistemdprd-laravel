<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laporan menyimpan wilayah terpilih sebagai relasi. Kolom kecamatan lama
     * tetap dipertahankan agar riwayat dan tampilan lama tidak berubah.
     */
    public function up(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->foreignId('dprd_region_id')
                ->nullable()
                ->after('kecamatan')
                ->constrained('dprd_regions')
                ->nullOnDelete();
        });

        $regions = DB::table('dprd_regions')->get(['id', 'name']);

        foreach ($regions as $region) {
            $shortName = preg_replace('/^Kecamatan\s+/i', '', $region->name) ?: $region->name;

            DB::table('laporan_masyarakat')
                ->whereNull('dprd_region_id')
                ->where(function ($query) use ($region, $shortName) {
                    $query->where('kecamatan', $region->name)
                        ->orWhere('kecamatan', $shortName);
                })
                ->update(['dprd_region_id' => $region->id]);
        }

        // Pelaporan lama yang tidak memiliki nama kecamatan persis tetap dapat
        // dipetakan dari anggota DPRD yang dituju.
        DB::table('laporan_masyarakat')
            ->whereNull('dprd_region_id')
            ->whereNotNull('dprd_member_id')
            ->orderBy('id')
            ->chunkById(200, function ($reports) {
                $memberRegions = DB::table('dprd_members')
                    ->whereIn('id', $reports->pluck('dprd_member_id')->unique()->all())
                    ->pluck('dprd_region_id', 'id');

                foreach ($reports as $report) {
                    $regionId = $memberRegions->get($report->dprd_member_id);

                    if ($regionId) {
                        DB::table('laporan_masyarakat')
                            ->where('id', $report->id)
                            ->update(['dprd_region_id' => $regionId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dprd_region_id');
        });
    }
};
