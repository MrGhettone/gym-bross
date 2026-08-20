<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Il frontend Vue (PWA) gira su un'origine diversa dal backend Laravel,
    | quindi le origini consentite sono definite esplicitamente tramite
    | env (FRONTEND_URL) invece di usare il wildcard '*', che non e'
    | compatibile con supports_credentials=true richiesto dall'autenticazione
    | Sanctum basata su cookie.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
