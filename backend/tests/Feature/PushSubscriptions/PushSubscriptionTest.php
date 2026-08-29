<?php

namespace Tests\Feature\PushSubscriptions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_a_push_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ]);

        $response->assertNoContent();
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => User::class,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_registering_the_same_endpoint_again_updates_it_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';
        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'first-key', 'auth' => 'first-token'],
        ]);
        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'second-key', 'auth' => 'second-token'],
        ]);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint, 'public_key' => 'second-key']);
    }

    public function test_it_requires_endpoint_and_keys(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/push-subscriptions', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_it_requires_authentication_to_subscribe(): void
    {
        $response = $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ]);

        $response->assertUnauthorized();
    }

    public function test_a_user_can_delete_their_subscription(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/abc123';
        $this->postJson('/api/v1/push-subscriptions', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'p256dh-key', 'auth' => 'auth-token'],
        ]);

        $response = $this->deleteJson('/api/v1/push-subscriptions?endpoint='.urlencode($endpoint));

        $response->assertNoContent();
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_deleting_without_an_endpoint_fails_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->deleteJson('/api/v1/push-subscriptions');

        $response->assertUnprocessable();
    }
}
