<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->fromFrontend()
            ->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }
}
