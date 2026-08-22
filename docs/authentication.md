# Autenticazione

Implementata in Fase 2. Laravel Sanctum a **token Bearer** (non cookie-based).

## Perché token e non cookie-based SPA

La prima implementazione usava Sanctum in modalità SPA cookie-based, pensata per frontend e backend sotto la stessa origine "logica". In produzione, però, frontend e backend sono deployati su **domini diversi** (nessun dominio condiviso): la registrazione dava sempre `CSRF token mismatch`.

Causa: un cookie impostato dal backend **non è leggibile via `document.cookie` da JavaScript in esecuzione su un dominio diverso** — è una restrizione del browser, non aggirabile con `SameSite`/`Secure` (quelli controllano solo se il cookie viene *inviato*, non se è *leggibile* da JS di un altro sito). Il frontend non poteva quindi mai leggere il cookie `XSRF-TOKEN` per rimandarlo indietro. Senza un dominio condiviso tra frontend e backend, l'auth cookie-based di Sanctum non è utilizzabile — da qui il passaggio a token Bearer (vedi decisione in [AGENTS.md](../AGENTS.md#decisioni-architetturali--problemi-aperti)).

## Approccio

- **Laravel Sanctum** (`laravel/sanctum` v4.3), token Bearer via tabella `personal_access_tokens`.
- `App\Models\User` usa il trait `HasApiTokens`.
- Nessun cookie/sessione coinvolti sulle rotte API: `bootstrap/app.php` non registra `EnsureFrontendRequestsAreStateful`, `config/cors.php` ha `supports_credentials: false`.
- Il frontend manda il token nell'header `Authorization: Bearer <token>` su ogni richiesta autenticata (vedi `src/services/api.ts`).

## Endpoint implementati

- `POST /api/v1/auth/register` — crea l'utente (`RegisterRequest`: `username`, `email`, `password` + `password_confirmation`), genera subito un token. Risposta `201`: `{"data": <UserResource>, "token": "..."}`.
- `POST /api/v1/auth/login` — `LoginRequest`: `email` + `password`. `422` con `{"errors":{"email":[...]}}` se le credenziali non sono corrette (stesso campo per non rivelare se l'email esiste). Rate limited: `throttle:6,1`. Risposta `200`: stesso formato di `register`.
- `POST /api/v1/auth/logout` — richiede `auth:sanctum` (Bearer), revoca **solo il token usato per la richiesta** (`$request->user()->currentAccessToken()->delete()`), non tutti i token dell'utente. Risponde `204`.
- `GET /api/v1/auth/me` — richiede `auth:sanctum` (Bearer), risponde con `UserResource` dell'utente autenticato.

Controller: `App\Http\Controllers\Api\V1\AuthController`. Nessun modello Eloquent grezzo esposto: le risposte passano sempre da `App\Http\Resources\UserResource` (`id`, `username`, `email`, `avatar`, `created_at` — mai `password`/`remember_token`/token).

## Sicurezza

- Password hashing nativo Laravel (cast `password => hashed` su `App\Models\User`).
- Rate limiting sul login (`throttle:6,1`).
- Validazione email/username univoci in registrazione (`RegisterRequest`).
- Middleware `auth:sanctum` su `logout` e `me`.
- `User::$fillable`/`$hidden` espliciti (property-based, non gli attributi PHP `#[Fillable]`/`#[Hidden]` che erano nello scaffold iniziale ma non esistono in questa versione del framework — vedi nota in AGENTS.md).
- **Tradeoff noto**: il token è persistito in `localStorage` sul frontend per sopravvivere al refresh pagina (non c'è un cookie `HttpOnly` che lo protegga da JS). È più esposto a XSS rispetto a un cookie `HttpOnly`, accettato consapevolmente perché l'alternativa (dominio condiviso tra frontend e backend) non è disponibile ora. Se in futuro frontend e backend finiscono sotto lo stesso dominio, si può tornare al cookie-based SPA auth.

## Frontend

- `src/services/api.ts` — client fetch centralizzato; `setAuthToken()` imposta il token in memoria, aggiunto come header `Authorization: Bearer <token>` su ogni richiesta.
- `src/services/auth.service.ts` — `register`, `login`, `logout`, `me`; `register`/`login` restituiscono `{ user, token }`.
- `src/stores/auth.ts` (Pinia) — stato utente + token globale; il token è persistito in `localStorage` (chiave `gymbros_token`) e ricaricato all'avvio dello store.
- `src/router/index.ts` — `beforeEach` guard: rotte con `meta.requiresAuth` richiedono utente autenticato (redirect a `login`), rotte con `meta.guestOnly` (`login`, `register`) redirigono a `home` se già autenticati. Il primo controllo chiama `auth.fetchMe()` una sola volta (stato `idle` → `ready`); se non c'è un token in `localStorage` salta direttamente la chiamata di rete.
- `src/views/LoginView.vue`, `src/views/RegisterView.vue` — form mobile-first in SCSS.

## Test

`backend/tests/Feature/Auth/`: `RegisterTest`, `LoginTest`, `LogoutTest`, `MeTest` — copertura di registrazione (incl. unicità username/email, conferma password, verifica che il token emesso funzioni davvero su una rotta protetta), login (credenziali corrette/errate, rate limit), logout (revoca del token, autenticato/non autenticato), accesso a `/me` (autenticato/guest → 401). I test usano token reali generati con `$user->createToken(...)` e header `Authorization`, non scorciatoie di test.
