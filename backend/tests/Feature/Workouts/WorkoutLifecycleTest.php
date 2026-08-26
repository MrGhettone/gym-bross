<?php

namespace Tests\Feature\Workouts;

use App\Enums\WorkoutStatus;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_view_their_workout_with_exercises_and_sets(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);
        WorkoutSet::factory()->create(['workout_exercise_id' => $workoutExercise->id, 'set_number' => 1]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.exercises');
        $response->assertJsonCount(1, 'data.exercises.0.sets');
    }

    public function test_a_stranger_cannot_view_someone_elses_workout(): void
    {
        $workout = Workout::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson("/api/v1/workouts/{$workout->id}");

        $response->assertForbidden();
    }

    public function test_the_owner_can_finish_an_active_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/workouts/{$workout->id}/finish");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'completed');
        $this->assertNotNull($workout->fresh()->finished_at);
    }

    public function test_finishing_an_already_finished_workout_fails(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->completed()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/workouts/{$workout->id}/finish");

        $response->assertForbidden();
    }

    public function test_the_owner_can_cancel_an_active_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/workouts/{$workout->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');
    }

    public function test_a_stranger_cannot_finish_someone_elses_workout(): void
    {
        $workout = Workout::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson("/api/v1/workouts/{$workout->id}/finish");

        $response->assertForbidden();
        $this->assertEquals(WorkoutStatus::Active, $workout->fresh()->status);
    }

    public function test_the_owner_can_delete_a_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/workouts/{$workout->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('workouts', ['id' => $workout->id]);
    }

    public function test_a_stranger_cannot_delete_someone_elses_workout(): void
    {
        $workout = Workout::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson("/api/v1/workouts/{$workout->id}");

        $response->assertForbidden();
    }
}
