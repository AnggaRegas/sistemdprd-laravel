<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Kelompok kemiripan aspirasi. Satu anggota DPRD dapat memiliki
         * beberapa kelompok. Kelompok menyimpan aspirasi utama yang dipilih
         * Super Admin serta jejak keputusan terakhir.
         */
        Schema::create('laporan_similarity_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dprd_member_id')
                ->constrained('dprd_members', 'id', 'similarity_group_member_fk')
                ->cascadeOnDelete();
            $table->string('kode', 40);
            $table->string('status', 20)->default('menunggu');
            $table->unsignedBigInteger('main_laporan_id')->nullable();
            $table->foreignId('decided_by')
                ->nullable()
                ->constrained('users', 'id', 'similarity_group_decider_fk')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('catatan')->nullable();
            $table->string('algorithm_version', 32);
            $table->decimal('threshold_high', 8, 6)->nullable();
            $table->decimal('threshold_medium', 8, 6)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['dprd_member_id', 'kode'], 'similarity_group_code_unique');
            $table->index('main_laporan_id', 'similarity_group_main_idx');
            $table->index(['status', 'decided_at'], 'similarity_group_status_idx');
        });

        /*
         * Keanggotaan satu aspirasi dalam satu kelompok. Unique index pada
         * laporan_masyarakat_id menjaga agar satu aspirasi tidak berada pada
         * dua kelompok yang saling bertentangan tanpa peninjauan.
         */
        Schema::create('laporan_similarity_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_similarity_group_id')
                ->constrained('laporan_similarity_groups', 'id', 'similarity_member_group_fk')
                ->cascadeOnDelete();
            $table->foreignId('laporan_masyarakat_id')
                ->constrained('laporan_masyarakat', 'id', 'similarity_member_laporan_fk')
                ->cascadeOnDelete();
            $table->string('peran', 20)->default('anggota');
            $table->string('similarity_level', 20)->nullable();
            $table->decimal('similarity_score', 8, 6)->nullable();
            $table->json('evidence')->nullable();
            $table->foreignId('decided_by')
                ->nullable()
                ->constrained('users', 'id', 'similarity_member_decider_fk')
                ->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique('laporan_masyarakat_id', 'similarity_member_laporan_unique');
            $table->unique(
                ['laporan_similarity_group_id', 'laporan_masyarakat_id'],
                'similarity_member_group_pair_unique'
            );
        });

        /*
         * Riwayat keputusan Super Admin. Tabel ini tidak pernah dihapus saat
         * pengelompokan ulang sehingga keputusan lama tetap dapat ditelusuri.
         */
        Schema::create('laporan_similarity_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_similarity_group_id')
                ->nullable()
                ->constrained('laporan_similarity_groups', 'id', 'similarity_decision_group_fk')
                ->nullOnDelete();
            $table->foreignId('laporan_masyarakat_id')
                ->nullable()
                ->constrained('laporan_masyarakat', 'id', 'similarity_decision_laporan_fk')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'id', 'similarity_decision_user_fk')
                ->nullOnDelete();
            $table->string('action', 40);
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->text('catatan')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['laporan_similarity_group_id', 'created_at'], 'similarity_decision_group_idx');
        });

        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->foreignId('similarity_group_id')
                ->nullable()
                ->after('dprd_member_id')
                ->constrained('laporan_similarity_groups', 'id', 'laporan_similarity_group_fk')
                ->nullOnDelete();
            $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('similarity_group_id');
            $table->string('similarity_status', 30)->nullable()->after('duplicate_of_id');
            $table->string('similarity_level', 20)->nullable()->after('similarity_status');
            $table->timestamp('similarity_reviewed_at')->nullable()->after('similarity_level');
            $table->foreignId('similarity_reviewed_by')
                ->nullable()
                ->after('similarity_reviewed_at')
                ->constrained('users', 'id', 'laporan_similarity_reviewer_fk')
                ->nullOnDelete();
            $table->text('similarity_note')->nullable()->after('similarity_reviewed_by');

            $table->index('duplicate_of_id', 'laporan_duplicate_of_idx');
            $table->index('similarity_status', 'laporan_similarity_status_idx');
        });

        Schema::table('laporan_similarity_results', function (Blueprint $table) {
            $table->string('similarity_level', 20)->nullable()->after('similarity_score');
            $table->json('evidence')->nullable()->after('similarity_level');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_similarity_results', function (Blueprint $table) {
            $table->dropColumn(['similarity_level', 'evidence']);
        });

        Schema::table('laporan_masyarakat', function (Blueprint $table) {
            $table->dropForeign(['similarity_group_id']);
            $table->dropForeign(['similarity_reviewed_by']);
            $table->dropIndex('laporan_duplicate_of_idx');
            $table->dropIndex('laporan_similarity_status_idx');
            $table->dropColumn([
                'similarity_group_id',
                'duplicate_of_id',
                'similarity_status',
                'similarity_level',
                'similarity_reviewed_at',
                'similarity_reviewed_by',
                'similarity_note',
            ]);
        });

        Schema::dropIfExists('laporan_similarity_decisions');
        Schema::dropIfExists('laporan_similarity_group_members');
        Schema::dropIfExists('laporan_similarity_groups');
    }
};
