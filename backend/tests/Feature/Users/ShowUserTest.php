<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_public_profile_by_username(): void
    {
        $viewer = User::factory()->create();
        $target = User::factory()->create(['username' => 'luigi', 'email' => 'luigi@example.com']);
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/users/luigi');

        $response->assertOk();
        $response->assertJson(['data' => ['id' => $target->id, 'username' => 'luigi']]);
        $response->assertJsonMissingPath('data.email');
    }

    public function test_it_returns_404_for_an_unknown_username(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/users/nobody');

        $response->assertNotFound();
    }

    public function test_it_requires_authentication(): void
    {
        User::factory()->create(['username' => 'luigi']);

        $response = $this->getJson('/api/v1/users/luigi');

        $response->assertUnauthorized();
    }
}
