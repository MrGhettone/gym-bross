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
/api/v1/auth/*           Fase 2 ✅
/api/v1/users/*          Fase 2/3
/api/v1/friends/*        Fase 3
/api/v1/workouts/*       Fase 4
/api/v1/exercises/*      Fase 4
/api/v1/notifications/*  Fase 7
```

## Endpoint attuali

### `GET /api/v1/ping`

Verifica di connettività frontend↔backend, nessuna logica applicativa. Nessuna autenticazione richiesta.

```json
{ "data": { "status": "ok", "service": "gym-bros-api" } }
```

### `POST /api/v1/auth/register`

Body: `username`, `email`, `password`, `password_confirmation`. Risposta `201` con `UserResource`, autentica subito l'utente (sessione). Vedi [docs/authentication.md](authentication.md).

### `POST /api/v1/auth/login`

Body: `email`, `password`. Risposta `200` con `UserResource`. Rate limited (`throttle:6,1`). `422` su credenziali errate.

### `POST /api/v1/auth/logout`

Richiede `auth:sanctum`. Risposta `204`.

### `GET /api/v1/auth/me`

Richiede `auth:sanctum`. Risposta `200` con `UserResource` dell'utente autenticato, `401` se non autenticato.
