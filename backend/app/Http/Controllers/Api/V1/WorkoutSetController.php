<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutSetRequest;
use App\Http\Requests\UpdateWorkoutSetRequest;
use App\Http\Resources\WorkoutSetResource;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkoutSetController extends Controller
{
    public function store(StoreWorkoutSetRequest $request, Workout $workout, WorkoutExercise $workoutExercise): JsonResponse
    {
        Gate::authorize('manageExercises', $workout);

        abort_unless($workoutExercise->workout_id === $workout->id, 404);

        $nextSetNumber = ($workoutExercise->sets()->max('set_number') ?? 0) + 1;

        $set = $workoutExercise->sets()->create([
            ...$request->validated(),
            'set_number' => $nextSetNumber,
        ]);

        return (new WorkoutSetResource($set))->response()->setStatusCode(201);
    }

    public function update(UpdateWorkoutSetRequest $request, WorkoutSet $workoutSet): WorkoutSetResource
    {
        Gate::authorize('manageExercises', $workoutSet->workoutExercise->workout);

        $workoutSet->update($request->validated());

        return new WorkoutSetResource($workoutSet);
    }

    public function destroy(WorkoutSet $workoutSet): JsonResponse
    {
        Gate::authorize('manageExercises', $workoutSet->workoutExercise->workout);

        $workoutSet->delete();

        return response()->json(null, 204);
    }
}
