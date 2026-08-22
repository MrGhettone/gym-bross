# AGENTS.md — Gym Bros

Memoria tecnica permanente del progetto. Leggere questo file prima di modificare qualsiasi codice.

## Obiettivo

PWA mobile-first per condividere allenamenti tra amici: account, amicizie, workout (esercizi/serie), feed sociale, notifiche push del browser. Nessuna app nativa iOS/Android: singola codebase web installabile come PWA.

## Stack

- **Frontend**: Vue 3, Vite, TypeScript, Vue Router, Pinia, SCSS (mobile-first, vedi `src/styles/`), `vite-plugin-pwa` (da Fase 6), Web Push API. Nessun framework CSS/UI aggiuntivo installato per ora.
- **Backend**: Laravel 12, PHP ^8.2, Eloquent, Laravel Sanctum (token Bearer, Fase 2), MySQL/MariaDB.
- Nessun altro framework PHP o JS di stato globale. Nessun Redis/WebSocket/microservizi salvo necessità reale futura.

## Architettura

- `frontend/` e `backend/` sono progetti completamente separati, comunicano solo via API REST JSON su `/api/v1/*`.
- Il frontend non è mai considerato trusted: ogni regola di autorizzazione, validazione e privacy vive nel backend.
- Autenticazione: Laravel Sanctum a **token Bearer** (non cookie-based) — frontend e backend sono su domini diversi in produzione (nessun dominio condiviso), e un cookie impostato dal backend non è leggibile via JS da un dominio diverso, indipendentemente da SameSite/Secure: il cookie-based SPA auth di Sanctum semplicemente non funziona in questo scenario. Deciso e cambiato in Fase 2 dopo un vero CSRF mismatch in produzione, vedi decisione sotto e [docs/authentication.md](./docs/authentication.md).
- CORS: origini consentite esplicite via `FRONTEND_URL` (env) in `backend/config/cors.php`, `supports_credentials=false` (nessun cookie da inviare cross-origin, il token va nell'header `Authorization`).

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

## Autenticazione (Fase 2 — implementata)

- Laravel Sanctum, token Bearer (`personal_access_tokens`). Endpoint: `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, `GET /api/v1/auth/me`. Register/login rispondono con `{"data": <user>, "token": "..."}`; il frontend manda il token in `Authorization: Bearer <token>`.
- Password hashing nativo Laravel, rate limiting sul login (`throttle:6,1`), validazione email/username univoci.
- Dettagli completi in [docs/authentication.md](./docs/authentication.md).

## Notifiche (pianificate, Fase 7)

- Web Push standard: Service Worker + Push API + Notification API + VAPID.
- Libreria PHP Web Push da scegliere in Fase 7 (verificare compatibilità Laravel 12/PHP 8.2+, manutenzione attiva) — non implementare la crittografia Web Push a mano.
- Chiavi VAPID solo via env (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`), mai committate.

## PWA (pianificata, Fase 6)

- `vite-plugin-pwa` per manifest + Service Worker (non ancora installato: verrà aggiunto in Fase 6 per rispettare lo sviluppo a fasi).

## Convenzioni

- Commit: Conventional Commits (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`), piccoli e coerenti (no commit che mischiano DB+API+UI+docs quando separabili).
- Non aggiungere dipendenze senza verificarne necessità, manutenzione, compatibilità e licenza.
- Non implementare funzionalità di fasi successive in anticipo.

## Comandi di sviluppo

Backend (richiede PHP 8.2+, vedi "Decisioni architetturali / problemi aperti"):

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

- **2026-08-20 — Setup iniziale**: scaffolding creato con `composer create-project laravel/laravel backend "^13.0" --ignore-platform-reqs` perché la macchina locale ha PHP 8.2 (Laravel 13 richiede PHP ^8.3, vincolo reale a livello di sintassi del framework, non solo di `composer.json`). Risultato: `composer install` completato, ma **`php artisan` non è eseguibile finché PHP locale non viene aggiornato**. Effetto collaterale scoperto in seguito: senza controlli di piattaforma, il `composer.lock` generato ha bloccato versioni Symfony (8.1.x) che richiedono **PHP ≥8.4.1**, non solo 8.3+. Da risolvere prima della Fase 2 (vedi voce sotto e [docs/development.md](./docs/development.md)).
- **2026-08-20 — CORS/Auth**: predisposto `config/cors.php` con origini esplicite da `FRONTEND_URL` e `supports_credentials=true`, in previsione di Sanctum cookie-based SPA auth (non ancora installato).
- **2026-08-20 — MySQL locale**: nessun server MySQL/MariaDB rilevato in locale; `.env` configurato per MySQL (`gym_bros` db) ma il server va installato/avviato separatamente prima delle migration.
- **2026-08-20 — Hosting backend su Railway (piano free)**: aggiunti `backend/start.sh` (migrate + `php artisan serve` su `0.0.0.0:$PORT`) e `backend/railway.json`. Prerequisito non automatizzabile da repo: nella dashboard Railway il servizio deve avere **Root Directory = `backend`** (il repo è un monorepo).
- **2026-08-20 — Da Railpack (auto-detect) a Dockerfile esplicito**: il rilevamento automatico di Railway (Railpack) ha continuato a scansionare la root dell'intero repo invece di `backend/`, sia su un servizio da Template sia su un servizio con Root Directory impostato manualmente — comportamento non affidabile lato piattaforma per questo progetto, non un problema nel codice. Aggiunto `backend/Dockerfile` + `backend/.dockerignore`, e `railway.json` aggiornato con `"builder": "DOCKERFILE"` per bypassare del tutto l'euristica di Railpack. Troubleshooting dettagliato in [docs/deployment.md](./docs/deployment.md).
- **2026-08-20 — Immagine Docker PHP 8.4, non 8.3**: `composer install` nel build Docker falliva su pacchetti Symfony che richiedono `php >=8.4.1` (bloccati nel `composer.lock` a causa del `--ignore-platform-reqs` iniziale, vedi sopra). `backend/Dockerfile` usa quindi `php:8.4-cli-bookworm`. Requisito locale aggiornato di conseguenza a PHP **8.4.1+** (non più solo 8.3+) per restare coerenti col lock file committato. **Superato dalla decisione del 2026-08-22 sotto.**
- **2026-08-22 — Downgrade a Laravel 12 per allinearsi a PHP 8.2 locale**: la macchina di sviluppo ha PHP 8.2.12 reale (non aggiornabile nell'immediato), quindi invece di inseguire PHP 8.4.1+ si è tornati a **Laravel 12** (richiede solo PHP ^8.2), eliminando il vincolo a cascata su Symfony 8.1.x introdotto da Laravel 13. `backend/composer.json` aggiornato (`php: ^8.3` → `^8.2`, `laravel/framework: ^13.17` → `^12.0`, `laravel/tinker: ^3.0` → `^2.10.1`; rimosso `laravel/pao`, non necessario/non compatibile con L12; script `dev` tornato al pattern `concurrently` di L12 invece del comando `artisan dev` introdotto in L13; script `test` senza il placeholder `@no_additional_args` di L13). `composer.lock` rigenerato con `composer update` **senza** `--ignore-platform-reqs`, direttamente su PHP 8.2.12 locale, quindi verificato compatibile per davvero (non solo dichiarato). `backend/Dockerfile` aggiornato a `php:8.2-cli-bookworm`. Effetto: `composer install`/`php artisan` ora funzionano in locale senza aggiornare PHP; l'immagine Docker di produzione è più leggera/comune. Da rivalutare solo se in futuro si vuole tornare a Laravel 13+ (richiederebbe di nuovo PHP ≥8.3/8.4 sia in locale che in produzione).
- **2026-08-22 — Downgrade toolchain frontend per allinearsi a Node 18 locale**: la macchina di sviluppo ha Node 18.17.1 reale; il frontend era scaffoldato con Vite 8 (richiede Node ^20.19.0 || >=22.12.0, usa `node:util`'s `styleText` non disponibile su Node 18), quindi `npm run build`/`npm run dev` fallivano subito con `SyntaxError` all'avvio. `frontend/package.json` aggiornato: `vite: ^8.2.0` → `^6.4.3` (ultima major compatibile con Node ^18.0.0), `@vitejs/plugin-vue: ^6.0.8` → `^5.2.4` (richiede peer `vite ^5||^6`), `@types/node: ^24.13.3` → `^18.19.0` (allineato alla versione Node reale). `vue-router` riportato da `^5.2.0` a `^4.6.4`: la v5 introduce un peer (opzionale ma comunque verificato da npm) su `vite ^7.3.0||^8.0.0` che riapriva lo stesso conflitto, e il codice (`src/router/index.ts`) usa solo API stabili già presenti in v4. `package-lock.json` rigenerato da zero con `npm install` pulito, verificato con `npm run build` (output in `dist/`) e `npm run dev` (server raggiungibile su `http://localhost:5173`, HTTP 200) su questa macchina. Da rivalutare se in futuro si aggiorna Node ad almeno 20.19+.
- **2026-08-22 — Bug nel modello `User` dello scaffold iniziale**: `app/Models/User.php` usava attributi PHP `#[Fillable([...])]`/`#[Hidden([...])]` (`Illuminate\Database\Eloquent\Attributes\Fillable`/`Hidden`) che **non esistono** nel Laravel 12 effettivamente installato (verificato: nessun file `Fillable.php`/`Hidden.php` in `vendor/laravel/framework/.../Eloquent/Attributes/`). Erano inerti: nessun errore a runtime perché PHP non risolve le classi degli attributi finché non vengono lette via Reflection, ma **mass assignment e `hidden` di fatto non funzionavano** (`$guarded` di default Eloquent è `['*']` senza un `$fillable` reale → `User::create()` avrebbe lanciato `MassAssignmentException`). Corretto usando le property standard `protected $fillable`/`protected $hidden`, uniche supportate in questa versione. Attenzione a scaffold generati in futuro: verificare sempre che le feature usate esistano davvero in `vendor/`, non fidarsi della sintassi generata.
- **2026-08-22 — Fase 2, Auth Sanctum implementata (cookie-based SPA)**: `composer require laravel/sanctum` (v4.3.3), `EnsureFrontendRequestsAreStateful` prependato al gruppo middleware `api` in `bootstrap/app.php`. `config/sanctum.php` pubblicato e modificato: `stateful` derivato da `FRONTEND_URL` (stessa fonte di `config/cors.php`) invece che da `SANCTUM_STATEFUL_DOMAINS` come default, per avere un solo posto da aggiornare. Niente tabella `personal_access_tokens`: non pubblicata la migration Sanctum perché l'app usa solo auth cookie-based (nessun token API/mobile pianificato, coerente con "nessuna app nativa" in Obiettivo) — se in futuro servissero token API andrà pubblicata. Migration `add_username_and_avatar_to_users_table`: rimuove `name` (non nello schema di AGENTS.md) e aggiunge `username` (unique) e `avatar` (nullable), coerente con lo schema `users` documentato. Verificato end-to-end con richieste `curl` reali (cookie jar + CSRF) oltre che con i feature test, non solo con test automatici. **Superato dalla decisione sotto**: in produzione, con frontend deployato su un dominio diverso dal backend Railway, questo approccio dava sempre "CSRF token mismatch" (vedi sotto per il perché).
- **2026-08-22 — Da cookie-based SPA a token Bearer**: la registrazione in produzione dava sempre `CSRF token mismatch`. Causa reale: frontend e backend sono su domini completamente diversi (frontend su un altro host, backend `*.up.railway.app`), e un cookie impostato dal backend **non è leggibile via `document.cookie` da JS in esecuzione su un dominio diverso** — non è un problema di `SameSite`/`Secure` (quelli controllano solo l'invio del cookie, non la leggibilità), è una restrizione del browser non aggirabile senza un dominio condiviso tra frontend e backend. Non essendoci un dominio condiviso disponibile, si è passati a Sanctum a **token Bearer**: rimosso `EnsureFrontendRequestsAreStateful` da `bootstrap/app.php`, `config/sanctum.php` semplificato (niente più `stateful`/`middleware`, solo `expiration`/`token_prefix`), pubblicata la migration `personal_access_tokens` (ora serve davvero), `User` usa `HasApiTokens`. `AuthController`: `register`/`login` rispondono con `{"data": <user>, "token": "..."}` (`$user->createToken('api')->plainTextToken`), `logout` revoca solo il token corrente (`$request->user()->currentAccessToken()->delete()`), `me` invariato (risolto automaticamente via Bearer da `auth:sanctum`, non più via sessione). `config/cors.php`: `supports_credentials` da `true` a `false` (nessun cookie coinvolto), rimosso `sanctum/csrf-cookie` da `paths`. Frontend: `api.ts` non usa più `ensureCsrfCookie`/`XSRF-TOKEN`, manda `Authorization: Bearer <token>`; il token è tenuto in memoria (`setAuthToken`) e persistito in `localStorage` da `stores/auth.ts` per sopravvivere al refresh pagina — tradeoff noto rispetto a un cookie `HttpOnly` (più esposto a XSS), accettato consapevolmente perché l'alternativa (dominio condiviso) non è disponibile ora. Test aggiornati per usare token reali (`$user->createToken(...)` + header `Authorization`) invece della sessione/Referer simulato. Dettagli completi in [docs/authentication.md](./docs/authentication.md).
