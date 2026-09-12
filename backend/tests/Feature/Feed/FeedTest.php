<?php

namespace Tests\Feature\Feed;

use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_workouts_per_day_for_self_and_accepted_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        $day = Carbon::parse('2026-09-10 08:00:00');
        Workout::factory()->completed()->create(['user_id' => $user->id, 'started_at' => $day]);
        Workout::factory()->completed()->create(['user_id' => $friend->id, 'started_at' => $day->copy()->addHours(2)]);
        Workout::factory()->completed()->create([
            'user_id' => $friend->id,
            'started_at' => $day->copy()->addDay(),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/summary?month=2026-09');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                ['date' => '2026-09-10', 'count' => 2],
                ['date' => '2026-09-11', 'count' => 1],
            ],
        ]);
    }

    public function test_summary_excludes_non_friends_and_cancelled_workouts(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $day = Carbon::parse('2026-09-10 08:00:00');
        Workout::factory()->completed()->create(['user_id' => $stranger->id, 'started_at' => $day]);
        Workout::factory()->cancelled()->create(['user_id' => $user->id, 'started_at' => $day]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/summary?month=2026-09');

        $response->assertOk();
        $response->assertJson(['data' => []]);
    }

    public function test_summary_defaults_to_the_current_month(): void
    {
        $user = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $user->id, 'started_at' => now()]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/summary');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_summary_requires_authentication(): void
    {
        $this->getJson('/api/v1/feed/summary')->assertUnauthorized();
    }

    public function test_day_shows_workouts_from_self_and_accepted_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        $day = Carbon::parse('2026-09-10 08:00:00');
        $own = Workout::factory()->completed()->create(['user_id' => $user->id, 'started_at' => $day]);
        $friendWorkout = Workout::factory()->create(['user_id' => $friend->id, 'started_at' => $day->copy()->addHours(2)]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($own->id));
        $this->assertTrue($ids->contains($friendWorkout->id));
    }

    public function test_day_excludes_workouts_from_non_friends(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $stranger->id, 'started_at' => '2026-09-10 08:00:00']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_day_excludes_workouts_from_pending_friends(): void
    {
        $user = User::factory()->create();
        $notYetFriend = User::factory()->create();
        Friendship::factory()->create(['requester_id' => $user->id, 'addressee_id' => $notYetFriend->id]);
        Workout::factory()->completed()->create(['user_id' => $notYetFriend->id, 'started_at' => '2026-09-10 08:00:00']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertJsonCount(0, 'data');
    }

    public function test_day_excludes_cancelled_workouts(): void
    {
        $user = User::factory()->create();
        Workout::factory()->cancelled()->create(['user_id' => $user->id, 'started_at' => '2026-09-10 08:00:00']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertJsonCount(0, 'data');
    }

    public function test_day_excludes_workouts_from_other_days(): void
    {
        $user = User::factory()->create();
        Workout::factory()->completed()->create(['user_id' => $user->id, 'started_at' => '2026-09-11 08:00:00']);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertJsonCount(0, 'data');
    }

    public function test_day_orders_by_start_time(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        Friendship::factory()->accepted()->create(['requester_id' => $user->id, 'addressee_id' => $friend->id]);

        $earlier = Workout::factory()->completed()->create([
            'user_id' => $user->id,
            'started_at' => '2026-09-10 07:00:00',
        ]);
        $later = Workout::factory()->completed()->create([
            'user_id' => $friend->id,
            'started_at' => '2026-09-10 09:00:00',
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/day?date=2026-09-10');

        $response->assertJsonPath('data.0.id', $earlier->id);
        $response->assertJsonPath('data.1.id', $later->id);
    }

    /**
     * Regressione: l'app gira in UTC, ma "che giorno e'" un allenamento deve
     * seguire il calendario italiano (Europe/Rome), non quello UTC. In
     * settembre l'Italia e' a UTC+2 (CEST): l'1:30 locale del 10 settembre
     * e' salvato come 23:30 UTC del 9. Deve contare/apparire sotto il 10,
     * non il 9.
     */
    public function test_summary_groups_by_the_italian_calendar_day_not_utc(): void
    {
        $user = User::factory()->create();
        Workout::factory()->completed()->create([
            'user_id' => $user->id,
            'started_at' => Carbon::parse('2026-09-09 23:30:00', 'UTC'),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/feed/summary?month=2026-09');

        $response->assertOk();
        $response->assertJson(['data' => [['date' => '2026-09-10', 'count' => 1]]]);
    }

    public function test_day_groups_by_the_italian_calendar_day_not_utc(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->completed()->create([
            'user_id' => $user->id,
            'started_at' => Carbon::parse('2026-09-09 23:30:00', 'UTC'),
        ]);

        Sanctum::actingAs($user);

        $onTheItalianDay = $this->getJson('/api/v1/feed/day?date=2026-09-10');
        $onTheItalianDay->assertJsonCount(1, 'data');
        $onTheItalianDay->assertJsonPath('data.0.id', $workout->id);

        $onTheUtcDay = $this->getJson('/api/v1/feed/day?date=2026-09-09');
        $onTheUtcDay->assertJsonCount(0, 'data');
    }

    public function test_day_requires_a_date_parameter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/feed/day')->assertUnprocessable();
    }

    public function test_day_requires_authentication(): void
    {
        $this->getJson('/api/v1/feed/day?date=2026-09-10')->assertUnauthorized();
    }
}
