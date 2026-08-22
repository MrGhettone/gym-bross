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
/api/v1/users/*          Fase 3 ✅ (solo lookup pubblico per username)
/api/v1/friends/*        Fase 3 ✅
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

Body: `username`, `email`, `password`, `password_confirmation`. Risposta `201`: `{"data": <UserResource>, "token": "..."}` — auth a token Bearer, non sessione/cookie (domini frontend/backend diversi in produzione). Vedi [docs/authentication.md](authentication.md).

### `POST /api/v1/auth/login`

Body: `email`, `password`. Risposta `200`, stesso formato di `register`. Rate limited (`throttle:6,1`). `422` su credenziali errate.

### `POST /api/v1/auth/logout`

Richiede `auth:sanctum` (header `Authorization: Bearer <token>`). Revoca solo il token usato per la richiesta. Risposta `204`.

### `GET /api/v1/auth/me`

Richiede `auth:sanctum` (header `Authorization: Bearer <token>`). Risposta `200` con `UserResource` dell'utente autenticato, `401` se non autenticato/token non valido.

### `GET /api/v1/users/{username}`

Richiede `auth:sanctum`. Lookup pubblico per username (serve a trovare l'utente a cui inviare una richiesta di amicizia). Risposta `200` con `PublicUserResource` (`id`, `username`, `avatar` — **mai** l'email, riservata al proprio `/me`). `404` se lo username non esiste.

### `GET /api/v1/friends`

Richiede `auth:sanctum`. Lista le relazioni (in qualsiasi stato/direzione) che coinvolgono l'utente autenticato. Filtro opzionale `?status=pending|accepted|rejected|blocked`. Risposta `200` con array di `FriendshipResource` (`id`, `status`, `direction`: `incoming`/`outgoing` relativo all'utente autenticato, `requester`/`addressee`: `PublicUserResource`, `created_at`).

### `POST /api/v1/friends`

Richiede `auth:sanctum`. Body: `username` (destinatario). Crea una richiesta `pending`. `422` se: username inesistente, è il proprio username, o esiste già una relazione tra i due utenti in qualsiasi direzione/stato. Risposta `201` con `FriendshipResource`.

### `PATCH /api/v1/friends/{friendship}/accept`

Richiede `auth:sanctum`. Solo l'`addressee` di una richiesta `pending` può accettarla. `403` altrimenti. Risposta `200` con `FriendshipResource` (`status: accepted`).

### `PATCH /api/v1/friends/{friendship}/reject`

Come `accept`, ma imposta `status: rejected`. Relazione rifiutata: definitiva, non è previsto un nuovo invio tra le stesse due persone (vincolo DB sulla coppia).

### `PATCH /api/v1/friends/{friendship}/block`

Richiede `auth:sanctum`. Una delle due parti può bloccare la relazione (in qualsiasi stato tranne già `blocked`). Risposta `200` con `FriendshipResource` (`status: blocked`).

### `DELETE /api/v1/friends/{friendship}`

Richiede `auth:sanctum`. Cancella la richiesta: solo il `requester` se `pending` (annulla), oppure una delle due parti se `accepted` (rimuove l'amicizia). `403` negli altri casi. Risposta `204`.
