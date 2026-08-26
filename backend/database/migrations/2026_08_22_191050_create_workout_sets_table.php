<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('set_number');
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedInteger('repetitions')->nullable();
            $table->unsignedInteger('duration')->nullable()->comment('secondi, per esercizi a tempo');
            $table->decimal('distance', 8, 2)->nullable()->comment('per esercizi cardio');
            $table->timestamps();

            $table->index(['workout_exercise_id', 'set_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
