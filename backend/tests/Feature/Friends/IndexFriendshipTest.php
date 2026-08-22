<?php

namespace Tests\Feature\Friends;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexFriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_lists_relationships_involving_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $stranger = User::factory()->create();

        $asRequester = Friendship::factory()->create(['requester_id' => $user->id, 'addressee_id' => $other->id]);
        $asAddressee = Friendship::factory()->create(['requester_id' => $other->id, 'addressee_id' => $user->id]);
        Friendship::factory()->create(['requester_id' => $other->id, 'addressee_id' => $stranger->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/friends');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($asRequester->id));
        $this->assertTrue($ids->contains($asAddressee->id));
    }

    public function test_it_filters_by_status(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $other->id]);
        Friendship::factory()->create(['requester_id' => $user->id, 'addressee_id' => $third->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/friends?status=accepted');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'accepted');
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/friends');

        $response->assertUnauthorized();
    }
}
