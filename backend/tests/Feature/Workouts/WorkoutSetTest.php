<?php

namespace Tests\Feature\Workouts;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutSetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_log_a_set(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/workouts/{$workout->id}/exercises/{$workoutExercise->id}/sets",
            ['weight' => 60, 'repetitions' => 10],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.set_number', 1);
        $response->assertJsonPath('data.repetitions', 10);
    }

    public function test_set_number_increments_automatically(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        WorkoutSet::factory()->create(['workout_exercise_id' => $workoutExercise->id, 'set_number' => 1]);
        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/workouts/{$workout->id}/exercises/{$workoutExercise->id}/sets",
            ['repetitions' => 8],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.set_number', 2);
    }

    public function test_a_set_requires_at_least_one_metric(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/workouts/{$workout->id}/exercises/{$workoutExercise->id}/sets",
            [],
        );

        $response->assertUnprocessable();
    }

    public function test_a_stranger_cannot_log_a_set(): void
    {
        $workout = Workout::factory()->create();
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(
            "/api/v1/workouts/{$workout->id}/exercises/{$workoutExercise->id}/sets",
            ['repetitions' => 8],
        );

        $response->assertForbidden();
    }

    public function test_the_owner_can_update_a_set(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        $set = WorkoutSet::factory()->create(['workout_exercise_id' => $workoutExercise->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/workout-sets/{$set->id}", ['repetitions' => 12]);

        $response->assertOk();
        $response->assertJsonPath('data.repetitions', 12);
    }

    public function test_the_owner_can_delete_a_set(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        $set = WorkoutSet::factory()->create(['workout_exercise_id' => $workoutExercise->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/workout-sets/{$set->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('workout_sets', ['id' => $set->id]);
    }

    public function test_a_stranger_cannot_update_or_delete_a_set(): void
    {
        $workout = Workout::factory()->create();
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        $set = WorkoutSet::factory()->create(['workout_exercise_id' => $workoutExercise->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/v1/workout-sets/{$set->id}", ['repetitions' => 1])->assertForbidden();
        $this->deleteJson("/api/v1/workout-sets/{$set->id}")->assertForbidden();
    }
}
