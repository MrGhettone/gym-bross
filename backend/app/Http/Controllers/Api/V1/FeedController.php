<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FriendshipStatus;
use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeedWorkoutResource;
use App\Models\Friendship;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    /**
     * Nessuna tabella "feed" dedicata: e' derivato al volo dai workout degli
     * amici accettati, coerente con AGENTS.md ("il feed usa richieste HTTP
     * normali", nessuno storage/broadcasting aggiuntivo). Solo workout
     * active/completed (un annullato non e' attivita' da mostrare), mai i
     * propri: il feed riguarda gli amici, non se stessi.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $friendIds = Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->involvingUser($request->user()->id)
            ->get()
            ->map(fn (Friendship $friendship) => $friendship->requester_id === $request->user()->id
                ? $friendship->addressee_id
                : $friendship->requester_id);

        $workouts = Workout::query()
            ->whereIn('user_id', $friendIds)
            ->whereIn('status', [WorkoutStatus::Active, WorkoutStatus::Completed])
            ->with('user')
            ->withCount('workoutExercises')
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return FeedWorkoutResource::collection($workouts);
    }
}
