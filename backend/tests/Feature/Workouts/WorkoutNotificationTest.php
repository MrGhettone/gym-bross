<?php

namespace Tests\Feature\Workouts;

use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use App\Notifications\WorkoutActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_workout_notifies_accepted_friends(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/workouts')->assertCreated();

        Notification::assertSentTo($friend, WorkoutActivityNotification::class);
        Notification::assertNotSentTo($stranger, WorkoutActivityNotification::class);
        Notification::assertNotSentTo($user, WorkoutActivityNotification::class);
    }

    public function test_finishing_a_workout_notifies_accepted_friends(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);
        $this->patchJson("/api/v1/workouts/{$workout->id}/finish")->assertOk();

        Notification::assertSentTo($friend, WorkoutActivityNotification::class);
    }

    public function test_no_notification_is_sent_without_accepted_friends(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/workouts')->assertCreated();

        Notification::assertNothingSent();
    }

    /**
     * Regressione reale in produzione: un fallimento nell'invio della
     * notifica (es. VAPID mal configurato) faceva rispondere 500 anche se
     * il workout era gia' stato salvato. L'azione dell'utente non deve mai
     * dipendere dal successo di un effetto collaterale come notificare gli
     * amici.
     */
    public function test_a_notification_delivery_failure_does_not_break_starting_a_workout(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('push service down'));

        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/workouts');

        $response->assertCreated();
        $this->assertDatabaseHas('workouts', ['user_id' => $user->id]);
    }
}
