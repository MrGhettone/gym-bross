<?php

namespace Database\Factories;

use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutSet>
 */
class WorkoutSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_exercise_id' => WorkoutExercise::factory(),
            'set_number' => 1,
            'weight' => fake()->randomFloat(2, 10, 100),
            'repetitions' => fake()->numberBetween(5, 15),
        ];
    }
}
