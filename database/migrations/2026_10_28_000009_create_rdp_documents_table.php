<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dokumen privat milik satu RDP. Berkas fisik disimpan pada disk local
     * (storage/app), bukan pada direktori public.
     */
    public function up(): void
    {
        Schema::create('rdp_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rdp_meeting_id')
                ->constrained('rdp_meetings')
                ->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('category', 40);
            $table->date('document_date')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rdp_meeting_id', 'category']);
            $table->index('uploaded_by');
        });

        DB::statement(
            "ALTER TABLE rdp_documents ADD CONSTRAINT rdp_documents_category_check "
            ."CHECK (category IN ('risalah', 'notula', 'undangan', 'daftar_hadir', 'materi', 'pendukung'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE rdp_documents DROP CHECK rdp_documents_category_check');

        Schema::dropIfExists('rdp_documents');
    }
};
