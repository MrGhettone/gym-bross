<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            // restrict: un esercizio nel catalogo condiviso non deve poter
            // essere cancellato se referenziato dallo storico di un workout.
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->index(['workout_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
