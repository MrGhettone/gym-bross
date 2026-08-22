<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'mario@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->fromFrontend()->postJson('/api/v1/auth/login', [
            'email' => 'mario@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'data' => ['email' => 'mario@example.com'],
        ]);

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'mario@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->fromFrontend()->postJson('/api/v1/auth/login', [
            'email' => 'mario@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $response = $this->fromFrontend()->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        User::factory()->create([
            'email' => 'mario@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->fromFrontend()->postJson('/api/v1/auth/login', [
                'email' => 'mario@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->fromFrontend()->postJson('/api/v1/auth/login', [
            'email' => 'mario@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(429);
    }
}
