<?php

namespace Tests\Feature\Exercises;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_exercises(): void
    {
        Exercise::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/exercises');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_filters_by_search(): void
    {
        Exercise::factory()->create(['name' => 'Bench Press']);
        Exercise::factory()->create(['name' => 'Squat']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/exercises?search=bench');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Bench Press');
    }

    public function test_a_user_can_create_an_exercise(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/exercises', [
            'name' => 'Deadlift',
            'description' => 'Hip hinge movement',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('exercises', ['name' => 'Deadlift']);
    }

    public function test_exercise_name_must_be_unique(): void
    {
        Exercise::factory()->create(['name' => 'Deadlift']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/exercises', ['name' => 'Deadlift']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v1/exercises')->assertUnauthorized();
        $this->postJson('/api/v1/exercises', ['name' => 'Deadlift'])->assertUnauthorized();
    }
}
