<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->integer('session_number');
            $table->date('teaching_date');
            $table->enum('session_shift', ['Sáng', 'Chiều']);
            $table->string('google_event_id')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'modified'])->default('pending');
            $table->timestamps();

            $table->index(['class_id', 'subject_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
