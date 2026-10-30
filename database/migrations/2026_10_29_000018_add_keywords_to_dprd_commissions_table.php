<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dprd_commissions', function (Blueprint $table) {
            $table->json('keywords')->nullable()->after('description');
        });

        $defaults = config('aspiration_classification.defaults', []);

        foreach ($defaults as $slug => $keywords) {
            DB::table('dprd_commissions')
                ->where('slug', $slug)
                ->whereNull('keywords')
                ->update([
                    'keywords' => json_encode($keywords, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('dprd_commissions', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
