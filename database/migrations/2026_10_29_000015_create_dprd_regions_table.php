<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wilayah penerimaan aspirasi dipisahkan dari kolom kecamatan asal dan
     * dapil anggota. Administrator memetakan anggota melalui kolom
     * dprd_members.dprd_region_id pada form anggota.
     */
    public function up(): void
    {
        Schema::create('dprd_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('type', 30)->default('kecamatan');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('dprd_regions')->insert([
            [
                'name' => 'Kecamatan Bacukiki',
                'slug' => 'kecamatan-bacukiki',
                'type' => 'kecamatan',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kecamatan Bacukiki Barat',
                'slug' => 'kecamatan-bacukiki-barat',
                'type' => 'kecamatan',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kecamatan Ujung',
                'slug' => 'kecamatan-ujung',
                'type' => 'kecamatan',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kecamatan Soreang',
                'slug' => 'kecamatan-soreang',
                'type' => 'kecamatan',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dprd_regions');
    }
};
