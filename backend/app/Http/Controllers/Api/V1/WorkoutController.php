<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkoutController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $workouts = $request->user()->workouts()->orderByDesc('started_at')->get();

        return WorkoutResource::collection($workouts);
    }

    public function store(StoreWorkoutRequest $request): JsonResponse
    {
        $workout = Workout::create([
            'user_id' => $request->user()->id,
            'started_at' => now(),
            'status' => WorkoutStatus::Active,
        ]);

        return (new WorkoutResource($workout))->response()->setStatusCode(201);
    }

    public function show(Workout $workout): WorkoutResource
    {
        Gate::authorize('view', $workout);

        return new WorkoutResource($workout->load('workoutExercises.exercise', 'workoutExercises.sets'));
    }

    public function finish(Workout $workout): WorkoutResource
    {
        Gate::authorize('finish', $workout);

        $workout->update(['status' => WorkoutStatus::Completed, 'finished_at' => now()]);

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
}
