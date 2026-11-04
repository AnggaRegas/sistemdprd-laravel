<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi aspirasi tidak lagi dibatasi dari formulir, sehingga kolom TEXT
     * (maks. ~64 KB) dinaikkan kapasitasnya agar teks panjang tidak terpotong.
     * Data lama tetap terbaca tanpa perubahan.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE laporan_masyarakat MODIFY isi MEDIUMTEXT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE laporan_masyarakat MODIFY isi TEXT NULL');
        }
    }
};
