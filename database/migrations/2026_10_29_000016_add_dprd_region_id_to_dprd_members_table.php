<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relasi wilayah aspirasi tidak memakai kolom kecamatan asal atau dapil
     * secara langsung. Data kecamatan yang sudah ada hanya dipakai sebagai
     * pemetaan awal bila namanya sama dengan wilayah yang tersedia.
     */
    public function up(): void
    {
        Schema::table('dprd_members', function (Blueprint $table) {
            $table->foreignId('dprd_region_id')
                ->nullable()
                ->after('kecamatan')
                ->constrained('dprd_regions')
                ->nullOnDelete();
        });

        $regions = DB::table('dprd_regions')->pluck('id', 'name');

        foreach ($regions as $name => $id) {
            $shortName = preg_replace('/^Kecamatan\s+/i', '', $name) ?: $name;

            DB::table('dprd_members')
                ->whereNull('dprd_region_id')
                ->where(function ($query) use ($name, $shortName) {
                    $query->where('kecamatan', $name)
                        ->orWhere('kecamatan', $shortName);
                })
                ->update(['dprd_region_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('dprd_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dprd_region_id');
        });
    }
};
