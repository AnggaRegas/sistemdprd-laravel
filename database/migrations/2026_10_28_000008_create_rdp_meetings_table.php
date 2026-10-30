<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rapat Dengar Pendapat selalu berada di bawah satu komisi.
     * Kode RDP unik per komisi, tetapi kode boleh dipakai kembali setelah
     * RDP lama di-soft delete melalui generated column active_code.
     */
    public function up(): void
    {
        Schema::create('rdp_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dprd_commission_id')
                ->constrained('dprd_commissions')
                ->restrictOnDelete();
            $table->string('title', 200);
            $table->string('code', 80)->nullable();
            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->string('location', 180)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draf');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dprd_commission_id', 'meeting_date']);
            $table->index(['dprd_commission_id', 'status']);
            $table->index('status');
        });

        DB::statement(
            "ALTER TABLE rdp_meetings ADD CONSTRAINT rdp_meetings_status_check "
            ."CHECK (status IN ('draf', 'terjadwal', 'berlangsung', 'selesai', 'dibatalkan'))"
        );

        DB::statement(
            "ALTER TABLE rdp_meetings ADD COLUMN active_code VARCHAR(80) "
            ."GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN code ELSE NULL END) STORED"
        );

        DB::statement(
            'CREATE UNIQUE INDEX rdp_meetings_commission_active_code_unique '
            .'ON rdp_meetings (dprd_commission_id, active_code)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX rdp_meetings_commission_active_code_unique ON rdp_meetings');
        DB::statement('ALTER TABLE rdp_meetings DROP CHECK rdp_meetings_status_check');

        Schema::dropIfExists('rdp_meetings');
    }
};
