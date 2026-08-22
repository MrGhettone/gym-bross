<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Il frontend Vue gira su un dominio diverso dal backend Laravel (nessun
    | dominio condiviso), quindi le origini consentite sono definite
    | esplicitamente tramite env (FRONTEND_URL) invece di usare il wildcard
    | '*'. L'autenticazione e' a token Bearer (Sanctum, non cookie-based:
    | un cookie impostato dal backend non e' leggibile via JS da un dominio
    | diverso), quindi supports_credentials resta false: nessun cookie da
    | inviare cross-origin, il token va nell'header Authorization.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
