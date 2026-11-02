<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_agendas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->string('image')->nullable();
            $table->string('event_status', 20)->default('upcoming');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('event_date');
            $table->index('event_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_agendas');
    }
};
