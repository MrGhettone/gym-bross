# API

## Convenzioni generali

- Base path versionato: `/api/v1/*`.
- Risposte JSON coerenti:
  - Successo: `{ "data": ... }`
  - Errore: `{ "message": "..." }` (+ `errors` per errori di validazione 422, formato standard Laravel)
- Codici HTTP usati secondo semantica: 200, 201, 204, 400, 401, 403, 404, 409, 422, 500.
- Le risposte che espongono modelli usano Laravel API Resources, mai modelli Eloquent grezzi.

## Struttura prevista (per fase)

```
/api/v1/auth/*           Fase 2
/api/v1/users/*          Fase 2/3
/api/v1/friends/*        Fase 3
/api/v1/workouts/*       Fase 4
/api/v1/exercises/*      Fase 4
/api/v1/notifications/*  Fase 7
```

## Endpoint attuali (Fase 1)

### `GET /api/v1/ping`

Verifica di connettività frontend↔backend, nessuna logica applicativa.

Risposta 200:

```json
{
  "data": {
    "status": "ok",
    "service": "gym-bros-api"
  }
}
```

Nessuna autenticazione richiesta. Endpoint temporaneo/di servizio: verrà rivalutato quando saranno presenti endpoint applicativi reali (potrebbe restare come health-check).
