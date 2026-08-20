# AGENTS.md — Gym Bros

Memoria tecnica permanente del progetto. Leggere questo file prima di modificare qualsiasi codice.

## Obiettivo

PWA mobile-first per condividere allenamenti tra amici: account, amicizie, workout (esercizi/serie), feed sociale, notifiche push del browser. Nessuna app nativa iOS/Android: singola codebase web installabile come PWA.

## Stack

- **Frontend**: Vue 3, Vite, TypeScript, Vue Router, Pinia, `vite-plugin-pwa` (da Fase 6), Web Push API. Nessun framework CSS/UI aggiuntivo installato per ora.
- **Backend**: Laravel 13, PHP ^8.3, Eloquent, Laravel Sanctum (da Fase 2), MySQL/MariaDB.
- Nessun altro framework PHP o JS di stato globale. Nessun Redis/WebSocket/microservizi salvo necessità reale futura.

## Architettura

- `frontend/` e `backend/` sono progetti completamente separati, comunicano solo via API REST JSON su `/api/v1/*`.
- Il frontend non è mai considerato trusted: ogni regola di autorizzazione, validazione e privacy vive nel backend.
- Autenticazione pianificata: Laravel Sanctum in modalità **SPA cookie-based** (non token Bearer), perché frontend e backend sono pensati per girare sotto la stessa origine "logica" in produzione. Decisione da confermare/implementare in Fase 2.
- CORS: origini consentite esplicite via `FRONTEND_URL` (env), `supports_credentials=true` in `backend/config/cors.php` — necessario per cookie di sessione Sanctum. Mai wildcard `*` insieme a `supports_credentials`.

## Struttura repository

```
gym-bros/
  frontend/
    src/
      assets/ components/ composables/ layouts/
      router/ services/ stores/ types/ utils/ views/
  backend/
    app/ config/ database/ routes/ tests/
  docs/
  README.md
  AGENTS.md
```

## Regole backend

- Struttura Laravel standard: Controllers, Form Requests, Models, Policies, Resources. Services solo se la logica è realmente condivisa/complessa. Niente Repository Pattern, niente Service Layer gigante.
- Ogni endpoint autenticato passa da middleware Sanctum; ogni autorizzazione passa da una Policy, mai solo da check nel frontend.
- Non restituire mai modelli Eloquent grezzi: usare API Resources e valutare sempre quali campi esporre.
- Non fidarsi mai dell'input frontend: validare sempre con Form Request (tipi, lunghezza, range, ownership, stato risorsa).

## Regole frontend

- Nessuna chiamata HTTP diretta nei componenti: tutto passa da `src/services/` (`api.ts` come client centralizzato + un service per dominio, es. `auth.service.ts`, `friends.service.ts`).
- Stato globale in Pinia store per dominio (`stores/`), non in componenti singoli.
- Mobile-first: priorità mobile → tablet → desktop. Durante il workout la UI deve richiedere il minor numero di tap possibile.

## Database

Tabelle previste (create solo quando la fase corrispondente le richiede):

- `users` (id, username, email, password, avatar, timestamps) — username/email univoci
- `friendships` (stati: pending, accepted, rejected, blocked)
- `exercises` (id, name, description, timestamps)
- `workouts` (id, user_id, started_at, finished_at, status: active/completed/cancelled) — un utente ha al massimo un workout `active`
- `workout_exercises` (pivot workout↔exercise, con `order`)
- `workout_sets` (workout_exercise_id, set_number, weight, repetitions, duration nullable, distance nullable)
- `push_subscriptions` (user_id, endpoint, p256dh, auth token, ...)

Ogni modifica schema passa da una migration Laravel. Mai modificare il DB a mano.

## API

- Versionate sotto `/api/v1/*`. Routing registrato in `backend/routes/api.php` (agganciato in `bootstrap/app.php` via `withRouting(api: ...)`).
- Risposte coerenti: successo `{ "data": ... }`, errore `{ "message": "..." }` (+ `errors` per 422 in stile Laravel standard).
- Endpoint attuale: `GET /api/v1/ping` — solo verifica di connettività frontend↔backend (Fase 1), da rimuovere o mantenere come health-check quando arriveranno le API reali.

## Autenticazione (pianificata, Fase 2)

- Laravel Sanctum, cookie-based SPA auth. Endpoint previsti: register, login, logout, `/me`.
- Password hashing nativo Laravel, rate limiting sul login, validazione email.

## Notifiche (pianificate, Fase 7)

- Web Push standard: Service Worker + Push API + Notification API + VAPID.
- Libreria PHP Web Push da scegliere in Fase 7 (verificare compatibilità Laravel 13/PHP 8.3+, manutenzione attiva) — non implementare la crittografia Web Push a mano.
- Chiavi VAPID solo via env (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`), mai committate.

## PWA (pianificata, Fase 6)

- `vite-plugin-pwa` per manifest + Service Worker (non ancora installato: verrà aggiunto in Fase 6 per rispettare lo sviluppo a fasi).

## Convenzioni

- Commit: Conventional Commits (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`), piccoli e coerenti (no commit che mischiano DB+API+UI+docs quando separabili).
- Non aggiungere dipendenze senza verificarne necessità, manutenzione, compatibilità e licenza.
- Non implementare funzionalità di fasi successive in anticipo.

## Comandi di sviluppo

Backend (richiede PHP 8.3+, vedi "Decisioni architetturali / problemi aperti"):

```bash
cd backend
composer install
php artisan serve
php artisan migrate
php artisan test
```

Frontend:

```bash
cd frontend
npm install
npm run dev
npm run build
```

## Regole di sicurezza

- Mai esporre password, hash, token, secret, chiavi VAPID private, stack trace, query SQL in produzione.
- Mass assignment protetto (Eloquent `$fillable`/`$guarded` espliciti su ogni model).
- Rate limiting su login. HTTPS + secure cookies + HttpOnly + SameSite in produzione.

## Decisioni architetturali / problemi aperti

- **2026-08-20 — Setup iniziale**: scaffolding creato con `composer create-project laravel/laravel backend "^13.0" --ignore-platform-reqs` perché la macchina locale ha PHP 8.2 (Laravel 13 richiede PHP ^8.3, vincolo reale a livello di sintassi del framework, non solo di `composer.json`). Risultato: `composer install` completato, ma **`php artisan` non è eseguibile finché PHP locale non viene aggiornato a 8.3+**. Da risolvere prima della Fase 2.
- **2026-08-20 — CORS/Auth**: predisposto `config/cors.php` con origini esplicite da `FRONTEND_URL` e `supports_credentials=true`, in previsione di Sanctum cookie-based SPA auth (non ancora installato).
- **2026-08-20 — MySQL locale**: nessun server MySQL/MariaDB rilevato in locale; `.env` configurato per MySQL (`gym_bros` db) ma il server va installato/avviato separatamente prima delle migration.
