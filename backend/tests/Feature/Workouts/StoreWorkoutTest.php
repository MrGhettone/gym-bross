<?php

namespace Tests\Feature\Workouts;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreWorkoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_start_a_workout(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/workouts');

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');
    }

    public function test_a_user_cannot_start_a_second_active_workout(): void
    {
        $user = User::factory()->create();
        Workout::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/workouts');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('workout');
    }

    public function test_a_user_can_start_a_workout_once_the_previous_one_is_finished(): void
    {
        $user = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/workouts');

        $response->assertCreated();
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/api/v1/workouts')->assertUnauthorized();
    }
}
