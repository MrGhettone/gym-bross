<?php

namespace Tests\Feature\Friends;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyFriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_requester_can_cancel_a_pending_request(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($requester);
        $response = $this->deleteJson("/api/v1/friends/{$friendship->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_the_addressee_cannot_cancel_a_pending_request(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($addressee);
        $response = $this->deleteJson("/api/v1/friends/{$friendship->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('friendships', ['id' => $friendship->id]);
    }

    public function test_either_participant_can_remove_an_accepted_friendship(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($addressee);
        $response = $this->deleteJson("/api/v1/friends/{$friendship->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('friendships', ['id' => $friendship->id]);
    }

    public function test_a_stranger_cannot_remove_a_friendship(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $stranger = User::factory()->create();
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($stranger);
        $response = $this->deleteJson("/api/v1/friends/{$friendship->id}");

        $response->assertForbidden();
    }
}
