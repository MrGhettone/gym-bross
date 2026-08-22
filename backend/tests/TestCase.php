<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Simula l'header Referer inviato dal browser dal frontend Vue, cosi'
     * EnsureFrontendRequestsAreStateful (vedi config/sanctum.php) attiva la
     * sessione per la richiesta di test, come farebbe per una richiesta
     * reale dallo stesso origin configurato in FRONTEND_URL.
     */
    protected function fromFrontend(): static
    {
        return $this->withHeader('Referer', 'http://'.config('sanctum.stateful')[0]);
    }
}
