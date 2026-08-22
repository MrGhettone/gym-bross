<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FriendshipController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Le rotte API sono versionate sotto /api/v1/*. Le rotte relative a
| workout, feed e notifiche verranno aggiunte nelle fasi successive.
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
    });
});
