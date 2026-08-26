<?php

namespace Tests\Feature\Workouts;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_add_an_exercise_to_an_active_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.order', 1);
        $response->assertJsonPath('data.exercise.id', $exercise->id);
    }

    public function test_order_increments_automatically(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        WorkoutExercise::factory()->create(['workout_id' => $workout->id, 'order' => 1]);
        $exercise = Exercise::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.order', 2);
    }

    public function test_a_stranger_cannot_add_an_exercise(): void
    {
        $workout = Workout::factory()->create();
        $exercise = Exercise::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_add_an_exercise_to_a_finished_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->completed()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
        ]);

        $response->assertForbidden();
    }

    public function test_the_owner_can_remove_an_exercise_from_the_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$workoutExercise->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('workout_exercises', ['id' => $workoutExercise->id]);
    }

    public function test_removing_an_exercise_that_belongs_to_a_different_workout_returns_404(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $otherWorkoutExercise = WorkoutExercise::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/workouts/{$workout->id}/exercises/{$otherWorkoutExercise->id}");

        $response->assertNotFound();
    }
}
