<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel arsips yang sudah ada tetap dipakai sebagai data dokumen.
     * Dokumen hierarkis dihubungkan ke folder; arsip lama yang belum
     * memiliki folder tetap aman karena kolomnya nullable.
     */
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->foreignId('archive_folder_id')
                ->nullable()
                ->after('kategori_arsip_id')
                ->constrained('archive_folders')
                ->restrictOnDelete();

            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropForeign(['archive_folder_id']);
            $table->dropColumn(['archive_folder_id', 'file_size']);
        });
    }
};
