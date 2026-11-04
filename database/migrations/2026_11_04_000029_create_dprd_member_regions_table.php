<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dprd_member_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dprd_member_id')->constrained('dprd_members')->cascadeOnDelete();
            $table->foreignId('dprd_region_id')->constrained('dprd_regions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['dprd_member_id', 'dprd_region_id']);
            $table->index('dprd_region_id');
        });

        // Carry forward explicit legacy links; the pivot is the canonical relation going forward.
        DB::table('dprd_members')->whereNotNull('dprd_region_id')->orderBy('id')->get(['id', 'dprd_region_id'])->each(function ($member) {
            DB::table('dprd_member_regions')->insertOrIgnore([
                'dprd_member_id' => $member->id,
                'dprd_region_id' => $member->dprd_region_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Backfill missing pivot links from the existing dapil/kecamatan fields.
        // Region IDs are looked up by database name, never assumed or hardcoded.
        $regions = DB::table('dprd_regions')->get(['id', 'name']);
        DB::table('dprd_members')->whereNull('deleted_at')->orderBy('id')->get(['id', 'dapil', 'kecamatan'])->each(function ($member) use ($regions) {
            $district = trim((string) ($member->dapil ?: $member->kecamatan));
            $district = preg_replace('/^Kecamatan\\s+/i', '', $district) ?: $district;

            if ($district === '') {
                return;
            }

            $region = $regions->first(function ($region) use ($district) {
                $name = preg_replace('/^Kecamatan\\s+/i', '', (string) $region->name) ?: $region->name;

                return mb_strtolower(trim($name)) === mb_strtolower(trim($district));
            });

            if ($region) {
                DB::table('dprd_member_regions')->insertOrIgnore([
                    'dprd_member_id' => $member->id,
                    'dprd_region_id' => $region->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dprd_member_regions');
    }
};
