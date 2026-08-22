<?php

namespace Tests\Feature\Friends;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransitionFriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_addressee_can_accept_a_pending_request(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($addressee);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/accept");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'accepted');
        $this->assertDatabaseHas('friendships', ['id' => $friendship->id, 'status' => FriendshipStatus::Accepted]);
    }

    public function test_the_requester_cannot_accept_their_own_request(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($requester);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/accept");

        $response->assertForbidden();
    }

    public function test_the_addressee_can_reject_a_pending_request(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($addressee);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/reject");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'rejected');
    }

    public function test_an_already_accepted_request_cannot_be_accepted_again(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($addressee);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/accept");

        $response->assertForbidden();
    }

    public function test_either_participant_can_block(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $friendship = Friendship::factory()->accepted()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($requester);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/block");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'blocked');
    }

    public function test_a_stranger_cannot_act_on_a_friendship(): void
    {
        $requester = User::factory()->create();
        $addressee = User::factory()->create();
        $stranger = User::factory()->create();
        $friendship = Friendship::factory()->create([
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);

        Sanctum::actingAs($stranger);
        $response = $this->patchJson("/api/v1/friends/{$friendship->id}/accept");

        $response->assertForbidden();
    }
}
