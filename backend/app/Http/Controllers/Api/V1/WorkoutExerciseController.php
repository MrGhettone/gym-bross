<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutExerciseRequest;
use App\Http\Resources\WorkoutExerciseResource;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkoutExerciseController extends Controller
{
    public function store(StoreWorkoutExerciseRequest $request, Workout $workout): JsonResponse
    {
        Gate::authorize('manageExercises', $workout);

        $nextOrder = ($workout->workoutExercises()->max('order') ?? 0) + 1;

        $workoutExercise = $workout->workoutExercises()->create([
            'exercise_id' => $request->validated('exercise_id'),
            'order' => $nextOrder,
        ]);

        return (new WorkoutExerciseResource($workoutExercise->load('exercise')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Workout $workout, WorkoutExercise $workoutExercise): JsonResponse
    {
        Gate::authorize('manageExercises', $workout);

        abort_unless($workoutExercise->workout_id === $workout->id, 404);

        $workoutExercise->delete();

        return response()->json(null, 204);
    }
}
