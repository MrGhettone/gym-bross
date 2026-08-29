<?php

namespace Tests\Feature\Feed;

use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_completed_and_active_workouts_from_accepted_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        $active = Workout::factory()->create(['user_id' => $friend->id]);
        $completed = Workout::factory()->completed()->create(['user_id' => $friend->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertTrue($ids->contains($completed->id));
    }

    public function test_it_works_regardless_of_who_sent_the_friend_request(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        // friend e' il requester stavolta, non l'addressee
        Friendship::factory()->accepted()->create(['requester_id' => $friend->id, 'addressee_id' => $user->id]);
        $workout = Workout::factory()->completed()->create(['user_id' => $friend->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $workout->id);
    }

    public function test_it_excludes_workouts_from_non_friends(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $stranger->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_it_excludes_workouts_from_pending_friends(): void
    {
        $user = User::factory()->create();
        $notYetFriend = User::factory()->create();
        Friendship::factory()->create(['requester_id' => $user->id, 'addressee_id' => $notYetFriend->id]);
        Workout::factory()->completed()->create(['user_id' => $notYetFriend->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertJsonCount(0, 'data');
    }

    public function test_it_excludes_cancelled_workouts(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);
        Workout::factory()->cancelled()->create(['user_id' => $friend->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertJsonCount(0, 'data');
    }

    public function test_it_excludes_the_users_own_workouts(): void
    {
        $user = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertJsonCount(0, 'data');
    }

    public function test_it_orders_by_most_recent_first(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        $older = Workout::factory()->completed()->create([
            'user_id' => $friend->id,
            'started_at' => now()->subDay(),
        ]);
        $newer = Workout::factory()->completed()->create([
            'user_id' => $friend->id,
            'started_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed');

        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.1.id', $older->id);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }
}
