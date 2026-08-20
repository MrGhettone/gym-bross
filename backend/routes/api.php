<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Le rotte API sono versionate sotto /api/v1/*. In questa fase iniziale
| e' presente solo un endpoint di verifica (/ping) usato per confermare
| che frontend e backend siano correttamente collegati. Le rotte relative
| ad autenticazione, amicizie, workout, feed e notifiche verranno
| aggiunte nelle fasi successive.
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
});
