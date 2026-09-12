<?php

namespace Tests\Feature\Friends;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreFriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_send_a_friend_request(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['username' => 'luigi']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/friends', ['username' => 'luigi']);

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'status' => 'pending',
                'direction' => 'outgoing',
                'requester' => ['id' => $user->id],
                'addressee' => ['id' => $target->id],
            ],
        ]);

        $this->assertDatabaseHas('friendships', [
            'requester_id' => $user->id,
            'addressee_id' => $target->id,
            'status' => FriendshipStatus::Pending,
        ]);
    }

    public function test_a_user_cannot_friend_themselves(): void
    {
        $user = User::factory()->create(['username' => 'mario']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/friends', ['username' => 'mario']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('username');
    }

    public function test_it_requires_an_existing_username(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/friends', ['username' => 'nobody']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('username');
    }

    public function test_it_rejects_a_duplicate_relationship_in_either_direction(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['username' => 'luigi']);
        Friendship::factory()->create([
            'requester_id' => $target->id,
            'addressee_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/friends', ['username' => 'luigi']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('username');
    }

    /**
     * Regressione reale in produzione: Friendship::scopeBetween() passava un
     * array a orWhere(['requester_id' => ..., 'addressee_id' => ...]), che in
     * Eloquent unisce le due condizioni con OR (non AND) perche' l'array
     * eredita il boolean della chiamata esterna. Risultato: qualunque
     * amicizia esistente in cui il destinatario compariva come requester_id
     * (es. l'aveva accettata lui stesso inviandola) faceva risultare "gia'
     * esistente" una relazione con un mittente completamente estraneo,
     * bloccando tutte le nuove richieste in arrivo.
     */
    public function test_accepting_unrelated_friendships_does_not_block_new_incoming_requests(): void
    {
        $target = User::factory()->create(['username' => 'luigi']);
        $otherFriend = User::factory()->create();
        // Il target ha inviato lui stesso una richiesta accettata a qualcun altro.
        Friendship::factory()->accepted()->create([
            'requester_id' => $target->id,
            'addressee_id' => $otherFriend->id,
        ]);

        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $response = $this->postJson('/api/v1/friends', ['username' => 'luigi']);

        $response->assertCreated();
        $this->assertDatabaseHas('friendships', [
            'requester_id' => $stranger->id,
            'addressee_id' => $target->id,
            'status' => FriendshipStatus::Pending,
        ]);
    }
}
