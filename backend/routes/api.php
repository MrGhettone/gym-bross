<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\FriendshipController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WorkoutController;
use App\Http\Controllers\Api\V1\WorkoutExerciseController;
use App\Http\Controllers\Api\V1\WorkoutSetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Le rotte API sono versionate sotto /api/v1/*. Le rotte relative a feed
| e notifiche verranno aggiunte nelle fasi successive.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'service' => 'gym-bros-api',
            ],
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users/{username}', [UserController::class, 'show']);

        Route::get('/friends', [FriendshipController::class, 'index']);
        Route::post('/friends', [FriendshipController::class, 'store']);
        Route::patch('/friends/{friendship}/accept', [FriendshipController::class, 'accept']);
        Route::patch('/friends/{friendship}/reject', [FriendshipController::class, 'reject']);
        Route::patch('/friends/{friendship}/block', [FriendshipController::class, 'block']);
        Route::delete('/friends/{friendship}', [FriendshipController::class, 'destroy']);

        Route::get('/exercises', [ExerciseController::class, 'index']);
        Route::post('/exercises', [ExerciseController::class, 'store']);

        Route::get('/workouts', [WorkoutController::class, 'index']);
        Route::post('/workouts', [WorkoutController::class, 'store']);
        Route::get('/workouts/{workout}', [WorkoutController::class, 'show']);
        Route::patch('/workouts/{workout}/finish', [WorkoutController::class, 'finish']);
        Route::patch('/workouts/{workout}/cancel', [WorkoutController::class, 'cancel']);
        Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy']);

        Route::post('/workouts/{workout}/exercises', [WorkoutExerciseController::class, 'store']);
        Route::delete('/workouts/{workout}/exercises/{workoutExercise}', [WorkoutExerciseController::class, 'destroy']);

        Route::post('/workouts/{workout}/exercises/{workoutExercise}/sets', [WorkoutSetController::class, 'store']);
        Route::patch('/workout-sets/{workoutSet}', [WorkoutSetController::class, 'update']);
        Route::delete('/workout-sets/{workoutSet}', [WorkoutSetController::class, 'destroy']);
    });
});
