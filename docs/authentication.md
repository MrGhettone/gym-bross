# Autenticazione

Implementata in Fase 2. Laravel Sanctum in modalità **SPA cookie-based** (non token Bearer).

## Approccio

- **Laravel Sanctum** (`laravel/sanctum` v4.3), modalità SPA cookie-based: nessuna tabella di token, autenticazione basata sulla sessione `web` (driver `database`).
- `EnsureFrontendRequestsAreStateful` prependato al gruppo middleware `api` (`bootstrap/app.php`): abilita cookie/sessione/CSRF sulle rotte `/api/v1/*` solo per le richieste che arrivano dal frontend (Origin/Referer nella lista domini stateful).
- `config/sanctum.php`: `stateful` derivato da `FRONTEND_URL` (stessa fonte usata da `config/cors.php`), non da un env dedicato — un solo posto da aggiornare per cambiare dominio frontend. Override possibile con `SANCTUM_STATEFUL_DOMAINS` (lista separata da virgole) solo se in futuro servissero domini extra.
- `config/cors.php` (Fase 1): `supports_credentials: true`, origini esplicite da `FRONTEND_URL`.
- Cookie CSRF: `GET /sanctum/csrf-cookie` (route registrata automaticamente dal package). Il frontend la chiama prima di ogni `register`/`login`/`logout` (vedi `src/services/api.ts#ensureCsrfCookie`), poi legge il cookie `XSRF-TOKEN` e lo rimanda come header `X-XSRF-TOKEN` sulle richieste che modificano stato (fetch non lo fa automaticamente come farebbe axios).

## Endpoint implementati

- `POST /api/v1/auth/register` — crea l'utente (`RegisterRequest`: `username`, `email`, `password` + `password_confirmation`), lo autentica subito, risponde `201` con `UserResource`.
- `POST /api/v1/auth/login` — `LoginRequest`: `email` + `password`. `422` con `{"errors":{"email":[...]}}` se le credenziali non sono corrette (stesso campo per non rivelare se l'email esiste). Rate limited: `throttle:6,1`.
- `POST /api/v1/auth/logout` — richiede `auth:sanctum`, invalida la sessione, risponde `204`.
- `GET /api/v1/auth/me` — richiede `auth:sanctum`, risponde con `UserResource` dell'utente autenticato.

Controller: `App\Http\Controllers\Api\V1\AuthController`. Nessun modello Eloquent grezzo esposto: le risposte passano sempre da `App\Http\Resources\UserResource` (`id`, `username`, `email`, `avatar`, `created_at` — mai `password`/`remember_token`).

## Sicurezza

- Password hashing nativo Laravel (cast `password => hashed` su `App\Models\User`).
- Rate limiting sul login (`throttle:6,1`).
- Validazione email/username univoci in registrazione (`RegisterRequest`).
- Middleware `auth:sanctum` su `logout` e `me`.
- `User::$fillable`/`$hidden` espliciti (property-based, non gli attributi PHP `#[Fillable]`/`#[Hidden]` che erano nello scaffold iniziale ma non esistono in questa versione del framework — vedi nota in AGENTS.md).

## Frontend

- `src/services/api.ts` — client fetch centralizzato + `ensureCsrfCookie()`.
- `src/services/auth.service.ts` — `register`, `login`, `logout`, `me`.
- `src/stores/auth.ts` (Pinia) — stato utente globale, usato dalla route guard.
- `src/router/index.ts` — `beforeEach` guard: rotte con `meta.requiresAuth` richiedono utente autenticato (redirect a `login`), rotte con `meta.guestOnly` (`login`, `register`) redirigono a `home` se già autenticati. Il primo controllo chiama `auth.fetchMe()` una sola volta (stato `idle` → `ready`) per rilevare una sessione già attiva (es. dopo refresh pagina).
- `src/views/LoginView.vue`, `src/views/RegisterView.vue` — form mobile-first in SCSS.

## Test

`backend/tests/Feature/Auth/`: `RegisterTest`, `LoginTest`, `LogoutTest`, `MeTest` — copertura di registrazione (incl. unicità username/email, conferma password), login (credenziali corrette/errate, rate limit), logout (autenticato/non autenticato), accesso a `/me` (autenticato/guest → 401).
