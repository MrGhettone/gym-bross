<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register(): void
    {
        $response = $this->fromFrontend()->postJson('/api/v1/auth/register', [
            'username' => 'mario',
            'email' => 'mario@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'username' => 'mario',
                'email' => 'mario@example.com',
            ],
        ]);
        $response->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'username' => 'mario',
            'email' => 'mario@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_registration_requires_a_unique_username(): void
    {
        User::factory()->create(['username' => 'mario']);

        $response = $this->fromFrontend()->postJson('/api/v1/auth/register', [
            'username' => 'mario',
            'email' => 'other@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('username');
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'mario@example.com']);

        $response = $this->fromFrontend()->postJson('/api/v1/auth/register', [
            'username' => 'other',
            'email' => 'mario@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->fromFrontend()->postJson('/api/v1/auth/register', [
            'username' => 'mario',
            'email' => 'mario@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'not-the-same',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('password');
    }
}
