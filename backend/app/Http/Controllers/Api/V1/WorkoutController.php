<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\User;
use App\Models\Workout;
use App\Notifications\WorkoutActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class WorkoutController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $workouts = $request->user()->workouts()->with('user')->orderByDesc('started_at')->get();

        return WorkoutResource::collection($workouts);
    }

    public function store(StoreWorkoutRequest $request): JsonResponse
    {
        $workout = Workout::create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'status' => WorkoutStatus::Active,
        ]);
        $workout->setRelation('user', $request->user());

        $this->notifyFriends($workout);

        return (new WorkoutResource($workout))->response()->setStatusCode(201);
    }

    public function show(Workout $workout): WorkoutResource
    {
        Gate::authorize('view', $workout);

        return new WorkoutResource($workout->load('workoutExercises.exercise', 'workoutExercises.sets', 'user'));
    }

    public function finish(Workout $workout): WorkoutResource
    {
        Gate::authorize('finish', $workout);

        $workout->update(['status' => WorkoutStatus::Completed, 'finished_at' => now()]);
        $this->notifyFriends($workout);

        return new WorkoutResource($workout);
    }

    public function cancel(Workout $workout): WorkoutResource
    {
        Gate::authorize('cancel', $workout);

        $workout->update(['status' => WorkoutStatus::Cancelled, 'finished_at' => now()]);

        return new WorkoutResource($workout);
    }

    public function destroy(Workout $workout): JsonResponse
    {
        Gate::authorize('delete', $workout);

        $workout->delete();

        return response()->json(null, 204);
    }

    /**
     * Notifica via Web Push gli amici accettati del proprietario del
     * workout (eventi MVP: inizio/fine allenamento, docs/notifications.md).
     *
     * Invio sincrono (non in coda, vedi docs/notifications.md): un errore
     * nella consegna (VAPID mal configurato, subscription scaduta, servizio
     * push irraggiungibile, ...) non deve mai far fallire l'azione
     * principale dell'utente (avviare/terminare il workout e' gia' stato
     * salvato) — per questo l'eccezione viene loggata, mai rilanciata.
     */
    private function notifyFriends(Workout $workout): void
    {
        $workout->loadMissing('user');

        $friends = User::query()->whereIn('id', $workout->user->acceptedFriendIds())->get();

        if ($friends->isEmpty()) {
            return;
        }

        try {
            Notification::send($friends, new WorkoutActivityNotification($workout));
        } catch (Throwable $exception) {
            Log::error('Invio notifica workout fallito', [
                'workout_id' => $workout->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
