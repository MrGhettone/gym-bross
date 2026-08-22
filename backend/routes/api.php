<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Le rotte API sono versionate sotto /api/v1/*. Le rotte relative ad
| amicizie, workout, feed e notifiche verranno aggiunte nelle fasi
| successive.
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
});
