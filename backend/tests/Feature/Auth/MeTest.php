<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create(['username' => 'mario']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $user->id,
                'username' => 'mario',
                'email' => $user->email,
            ],
        ]);
        $response->assertJsonMissingPath('data.password');
    }

    public function test_a_guest_cannot_fetch_a_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }
}
