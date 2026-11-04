<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->foreignId('disetujui_oleh')
                ->nullable()
                ->after('assigned_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable()->after('disetujui_oleh');
            $table->index('disetujui_at');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropForeign(['disetujui_oleh']);
            $table->dropIndex(['disetujui_at']);
            $table->dropColumn(['disetujui_oleh', 'disetujui_at']);
        });
    }
};
