<?php

namespace Database\Factories;

use App\Enums\WorkoutStatus;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'started_at' => now(),
            'status' => WorkoutStatus::Active,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => WorkoutStatus::Completed,
            'finished_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => WorkoutStatus::Cancelled,
            'finished_at' => now(),
        ]);
    }
}
