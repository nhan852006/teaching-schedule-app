<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->integer('session_number');
            $table->text('content');
            $table->integer('theory_time')->default(0);
            $table->integer('practice_time')->default(0);
            $table->timestamps();

            $table->unique(['subject_id', 'session_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_contents');
    }
};
