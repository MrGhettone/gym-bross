<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API token auth (non SPA cookie auth)
    |--------------------------------------------------------------------------
    |
    | Frontend e backend sono su domini diversi in produzione (nessun dominio
    | condiviso), quindi l'auth cookie-based di Sanctum non e' utilizzabile:
    | il cookie XSRF-TOKEN impostato dal backend non e' leggibile via JS dal
    | dominio del frontend, indipendentemente da SameSite/Secure. Si usa
    | quindi solo l'auth a token Bearer (vedi AuthController), niente
    | EnsureFrontendRequestsAreStateful in bootstrap/app.php, niente dominio
    | stateful da configurare qui.
    |
    */

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

];
