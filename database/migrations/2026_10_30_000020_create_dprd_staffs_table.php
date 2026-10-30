<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data referensi staf pendamping dipisahkan dari akun login.
     *
     * Nama pada tabel ini berasal dari daftar resmi dan tidak pernah dipakai
     * sebagai username atau kata sandi. Akun login tetap dibuat administrator
     * melalui fitur Kelola Akun Staf dan dihubungkan lewat users.dprd_staff_id.
     */
    public function up(): void
    {
        Schema::create('dprd_staffs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dprd_staffs');
    }
};
