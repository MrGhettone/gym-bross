# Autenticazione

Non ancora implementata (pianificata per Fase 2). Questo documento descrive la direzione architetturale decisa, da implementare e confermare in Fase 2.

## Approccio

- **Laravel Sanctum**, modalità SPA cookie-based (non token Bearer), coerente con frontend e backend gestiti come un'unica architettura logica.
- Sessione basata su cookie HttpOnly + Secure (in produzione) + SameSite appropriato.
- `config/cors.php` già predisposto con `supports_credentials: true` e origini esplicite da `FRONTEND_URL` (necessario prerequisito per i cookie cross-origin in sviluppo, dove frontend e backend girano su porte diverse).

## Endpoint pianificati

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me` (o `/api/v1/users/me`)

## Sicurezza pianificata

- Password hashing con le funzioni native Laravel (bcrypt/argon2, mai testo in chiaro).
- Rate limiting sul login.
- Validazione email in registrazione.
- Middleware Sanctum su tutte le rotte autenticate.
- Nessuna password/hash/token restituito nelle risposte API.

## Da fare in Fase 2

- `composer require laravel/sanctum` (non ancora installato).
- Configurazione `SESSION_DOMAIN` / stateful domains per l'ambiente di sviluppo.
- Migration/tabelle Sanctum necessarie.
- Form Requests: `RegisterRequest`, `LoginRequest`.
- Feature test: registrazione, login, logout, accesso a rotta protetta senza sessione → 401.
