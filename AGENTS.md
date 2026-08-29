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

- `users` ✅ (id, username, email, password, avatar, timestamps) — username/email univoci
- `friendships` ✅ (stati: pending, accepted, rejected, blocked)
- `exercises` ✅ (id, name, description, timestamps) — catalogo condiviso, non per-utente
- `workouts` ✅ (id, user_id, started_at, finished_at, status: active/completed/cancelled) — un utente ha al massimo un workout `active`
- `workout_exercises` ✅ (pivot workout↔exercise, con `order`)
- `workout_sets` ✅ (workout_exercise_id, set_number, weight, repetitions, duration nullable, distance nullable)
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

## Amicizie (Fase 3 — implementata)

- Modello `Friendship` con enum `App\Enums\FriendshipStatus` (pending/accepted/rejected/blocked), `FriendshipPolicy` per ogni transizione (accept/reject/cancel/remove/block), `PublicUserResource` (mai l'email) per non esporre dati privati di altri utenti.
- Endpoint: `GET/POST /api/v1/friends`, `PATCH /api/v1/friends/{id}/accept|reject|block`, `DELETE /api/v1/friends/{id}`, più `GET /api/v1/users/{username}` per cercare a chi inviare la richiesta.
- Vincolo "nessuna coppia duplicata": unique DB sulla direzione esatta, controllo applicativo nel Form Request per la direzione opposta — vedi decisione sotto per il perché non è esprimibile solo con un vincolo DB.
- Dettagli completi in [docs/api.md](./docs/api.md) e [docs/database.md](./docs/database.md).

## Workout (Fase 4 — implementata)

- Modelli `Exercise` (catalogo condiviso, chiunque autenticato può crearne), `Workout`, `WorkoutExercise`, `WorkoutSet`. Enum `App\Enums\WorkoutStatus` (active/completed/cancelled).
- `WorkoutPolicy` copre anche esercizi/serie del workout (ability `manageExercises`) invece di avere policy dedicate per `WorkoutExercise`/`WorkoutSet`: sono sotto-risorse senza proprio owner, "posso modificare questo workout" è la domanda giusta anche per loro.
- Un workout `active` è l'unico modificabile (aggiungere/rimuovere esercizi, loggare/cancellare serie); `completed`/`cancelled` sono storico di sola lettura.
- `order` (workout_exercises) e `set_number` (workout_sets) assegnati automaticamente dal backend (max esistente + 1), il frontend non li passa mai.
- Endpoint: `GET/POST /api/v1/exercises`, `GET/POST /api/v1/workouts`, `GET /api/v1/workouts/{id}`, `PATCH .../finish|cancel`, `DELETE /api/v1/workouts/{id}`, `POST/DELETE /api/v1/workouts/{id}/exercises[/{id}]`, `POST /api/v1/workouts/{id}/exercises/{id}/sets`, `PATCH/DELETE /api/v1/workout-sets/{id}`.
- Dettagli completi in [docs/api.md](./docs/api.md) e [docs/database.md](./docs/database.md).

## Feed (Fase 5 — implementata)

- Nessuna tabella dedicata: derivato al volo dai `workouts` (`active`/`completed`, mai `cancelled`, mai i propri) degli amici con relazione `accepted` — coerente con "il feed usa richieste HTTP normali, niente storage/broadcasting aggiuntivo".
- `WorkoutPolicy::view` esteso: non più solo il proprietario, anche un amico accettato può vedere il dettaglio di un workout (altrimenti il feed linkerebbe a pagine sempre `403`). Le altre ability (`finish`/`cancel`/`manageExercises`/`delete`) restano solo per il proprietario.
- `GET /api/v1/feed`, nessuna paginazione (limite 50 risultati) — coerente con la semplicità delle altre liste dell'app, da rivedere se il volume cresce.
- Frontend: `FeedView.vue` linka a `/workouts/{id}` (già esistente per i propri workout); `WorkoutDetailView.vue` ora distingue proprietario da amico (`isOwner`) e mostra i controlli di modifica solo al proprietario, non solo in base allo stato `active`.
- Dettagli completi in [docs/api.md](./docs/api.md).

## Notifiche (Fase 7 — implementata)

- Web Push standard: Service Worker custom (`frontend/src/sw.ts`) + Push API + Notification API + VAPID.
- Libreria PHP: `laravel-notification-channels/webpush` (su `minishlink/web-push`) — non implementata la crittografia Web Push a mano.
- Chiavi VAPID solo via env (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`), mai committate. Generate con `php artisan webpush:vapid`.
- Eventi: amico inizia/termina un workout → `WorkoutActivityNotification` a tutti gli amici `accepted` (`User::acceptedFriendIds()`). Invio sincrono, non in coda (nessun worker in esecuzione sull'infra attuale).
- Dettagli completi in [docs/notifications.md](./docs/notifications.md).

## PWA (Fase 6 — implementata)

- `vite-plugin-pwa` per manifest + Service Worker (`generateSW`, `registerType: autoUpdate`). Nessuna regola `runtimeCaching` per `/api/*`: solo l'app shell è precachata, i dati restano sempre in rete.
- Icone generate una tantum da `favicon.svg` (icona maskable da sorgente dedicata a sfondo pieno). `@vite-pwa/assets-generator` non è una dipendenza permanente (porta `sharp`, con una CVE nota) — vedi decisione sotto e [docs/pwa.md](./docs/pwa.md) per come rigenerarle.
- Dettagli completi in [docs/pwa.md](./docs/pwa.md).

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
- **2026-08-22 — Fase 3, Amicizie implementata**: migration `friendships` (`requester_id`/`addressee_id` FK cascade, `status` enum, unique su `(requester_id, addressee_id)`). `App\Enums\FriendshipStatus` (string-backed) invece di stringhe libere. `FriendshipPolicy` con abilità dedicate per transizione (`accept`/`reject`/`cancel`/`remove`/`block`), non i soli CRUD di default — ogni transizione ha regole diverse su chi può farla e da quale stato. `StoreFriendshipRequest` blocca sia l'auto-richiesta sia qualunque relazione già esistente tra i due utenti **in entrambe le direzioni** (query `Friendship::between()`), perché il vincolo unique DB copre solo la direzione esatta, non quella opposta. Reject è trattato come stato terminale (nessun nuovo invio permesso dopo un rifiuto, per restare semplici in questa fase — riconsiderare se serve un flusso di "riprova"). Aggiunta `PublicUserResource` (id/username/avatar, **mai** l'email) distinta da `UserResource`: un utente non deve vedere l'email di un altro solo perché gli è amico. `GET /api/v1/users/{username}` aggiunto per permettere al frontend di cercare a chi inviare una richiesta (necessario perché gli utenti non si conoscono per ID). Controller usa `Gate::authorize()` (non `$this->authorize()`, il trait `AuthorizesRequests` non è nel `Controller` base di questo scaffold Laravel 12 minimale). Verificato end-to-end con `curl` reali oltre ai 20 feature test nuovi. Dettagli in [docs/api.md](./docs/api.md) e [docs/database.md](./docs/database.md).
- **2026-08-22 — Errore proprio nella generazione delle migration di Fase 4**: `php artisan make:migration create_exercises_table` e `create_workouts_table` lanciati nello stesso batch/secondo hanno ricevuto lo **stesso timestamp** (`..._191048_...`). Nello scrivere il contenuto ho passato al tool `Write` un timestamp sbagliato per `create_workouts_table` (`191049` invece di `191048`, quello realmente generato da artisan), creando un file duplicato con lo stub vuoto rimasto nel path corretto e il contenuto vero in un path spurio con lo stesso timestamp di `workout_exercises`. `migrate` ha fallito a metà ("Table 'workouts' already exists") perché la migration-stub era comunque valida SQL e girata per prima. Risolto cancellando il duplicato, rimettendo il contenuto corretto nel file con il timestamp realmente generato da artisan, e `migrate:fresh` in locale per ripartire pulito (sicuro: solo dati di sviluppo/test). **Lezione**: quando `make:migration` genera più file nello stesso secondo, verificare sempre il nome file esatto riportato in output prima di scrivere il contenuto, non fidarsi di ricostruirlo a mente.
- **2026-08-22 — Fase 4, Workout implementata**: migration `exercises` (catalogo condiviso, `name` unique, nessun `user_id`), `workouts` (`user_id` FK cascade, `status` enum, vincolo "un solo `active` per utente" applicativo in `StoreWorkoutRequest` — non esprimibile come unique DB standard), `workout_exercises` (FK `exercise_id` con **restrict** on delete, non cascade: un esercizio nello storico non deve poter sparire), `workout_sets`. `App\Enums\WorkoutStatus`. `WorkoutPolicy` copre anche `WorkoutExercise`/`WorkoutSet` con l'ability `manageExercises` invece di policy dedicate per ognuno: sono sotto-risorse senza proprio owner, autorizzarle risalendo al workout padre è la scelta naturale (stesso pattern di `Gate::authorize()` usato in Fase 3). Solo un workout `active` è modificabile: `completed`/`cancelled` sono storico di sola lettura (decisione di scope per restare semplici, non per un vincolo tecnico). `order`/`set_number` assegnati automaticamente dal backend (`max()+1`), mai passati dal client. Frontend: form di aggiunta esercizio con un solo input (tenta `create`, se 422 per nome duplicato cerca l'esistente per nome esatto e lo riusa) per ridurre i tap, coerente con la priorità mobile-first di AGENTS.md; form di log serie con 4 campi numerici sempre visibili (peso/rip/durata/distanza) invece di richiedere un tap per "mostra altri campi". Nessuna UI per modificare una serie già loggata (solo cancella+ri-aggiungi): il backend supporta `PATCH /workout-sets/{id}` ma il frontend non lo usa ancora, scelta di scope non di capacità mancante. 64 feature test totali (backend), verificato anche end-to-end con `curl` reali (esercizio → workout → aggiungi esercizio → logga serie → dettaglio → termina). Dettagli in [docs/api.md](./docs/api.md) e [docs/database.md](./docs/database.md).
- **2026-08-29 — Fase 5, Feed implementato**: nessuna tabella dedicata (lo schema in AGENTS.md non ne prevedeva una) — derivato al volo da `workouts` filtrando su amici `accepted` (`FeedController`), coerente con "il feed usa richieste HTTP normali" in AGENTS.md. Esclusi i workout `cancelled` (non e' attivita' da mostrare) e i propri (il feed riguarda gli amici). `WorkoutPolicy::view` esteso per permettere anche a un amico accettato (non solo al proprietario) di vedere il dettaglio — necessario perche' altrimenti ogni link dal feed avrebbe dato `403`; le altre ability (modifica/cancellazione) restano owner-only, nessun cambiamento li'. Di conseguenza `WorkoutResource` ora include sempre `user` (`PublicUserResource`), anche per `index`/`store`/`show` sul proprio workout: prima non serviva perche' l'owner era implicito, ora il frontend deve poter distinguere "il mio workout" da "workout di un amico" per decidere se mostrare i controlli di modifica (`WorkoutDetailView.vue`: nuovo `isOwner` combinato con `isActive` in `canEdit`, invece del solo controllo sullo stato). Nessuna paginazione sul feed (limite 50), stessa scelta di semplicita' delle altre liste dell'app. 10 feature test nuovi (74 totali backend: 8 sul feed, 2 sulla policy di view estesa agli amici), verificato anche end-to-end con `curl` reali.
- **2026-08-29 — Fase 6, PWA implementata; workbox pinnato a Node 18**: le versioni piu' recenti di `workbox-build`/`workbox-window` (>=7.4.0) richiedono Node >=20, incompatibile con Node 18.17.1 locale (stesso tema delle decisioni precedenti su Laravel/Vite). Pinnate a `workbox-build: 7.3.0` e `workbox-window: 7.3.0` **esatti** (non `^7.3.0`: quel range risolveva comunque a 7.4.1, dentro il range ma con requisito Node piu' alto — lezione: quando l'obiettivo e' evitare una fascia di versioni specifica, pin esatto, non caret). Verificato con una build reale (`npm run build`) che `workbox-build` genera davvero `sw.js` su Node 18, non solo che l'installazione non fallisce. **Vulnerabilita' nota accettata**: `workbox-build@7.3.0` dipende da `@rollup/plugin-terser` che dipende da una versione di `serialize-javascript` con una CVE nota (RCE via input non fidato); non esiste una versione di `@rollup/plugin-terser` compatibile che la risolva senza forzare `workbox-build` a 7.4.x (Node 20+). Rischio pratico giudicato trascurabile: e' un tool solo di build che minifica codice nostro (il service worker generato), mai input esterno/utente. `@vite-pwa/assets-generator` (usato solo per generare le icone da `favicon.svg`, dipende da `sharp` con una CVE separata) **non e' rimasto tra le dipendenze**: installato temporaneamente, eseguito una volta, disinstallato — vedi [docs/pwa.md](./docs/pwa.md) per rigenerarle in futuro. Icona maskable generata a parte da `src/assets/pwa-maskable-source.svg` (sfondo pieno, senza gli angoli arrotondati di `favicon.svg`): il preset "minimal" del generatore aggiungeva padding extra attorno alle icone con gli angoli gia' arrotondati nell'SVG sorgente, che con una maschera circolare di sistema avrebbe mostrato bordi bianchi visibili. Nessuna regola `runtimeCaching` per `/api/*` in `vite.config.ts`: solo l'app shell e' precachata, deliberato (AGENTS.md/docs/pwa.md: niente caching aggressivo dei dati). Verificato servendo `dist/` con `npm run preview`: manifest, service worker e tutte le icone rispondono `200` con i content-type corretti.
- **2026-08-29 — Fase 7, Web Push implementata**: libreria `laravel-notification-channels/webpush` (su `minishlink/web-push`), risolve pulita con Laravel 12/PHP 8.2, nessuna vulnerabilità nota. **Bug ambiente scoperto**: `php artisan webpush:vapid` falliva con "Unable to create the key" — l'estensione OpenSSL di questo PHP su Windows/XAMPP non trova il proprio file di configurazione (`openssl_pkey_new` con curva EC richiede `openssl.cnf`, mai risolto senza la variabile d'ambiente `OPENSSL_CONF`). XAMPP include un `openssl.cnf` dedicato in `C:\xampp\php\extras\openssl\`, semplicemente non referenziato di default. Risolto lanciando il comando con `OPENSSL_CONF="C:\xampp\php\extras\openssl\openssl.cnf" php artisan webpush:vapid` — verificato con un test isolato di `openssl_pkey_new()` prima di ripetere il comando reale, non solo per tentativi. Schema `push_subscriptions` adottato così come lo genera il pacchetto (polimorfico `subscribable_type`/`subscribable_id`, colonna `public_key` non `p256dh`) invece di replicare esattamente lo schema ipotizzato in AGENTS.md — restare compatibili con le API standard del pacchetto (`updatePushSubscription`/`deletePushSubscription`) vale più della corrispondenza letterale col nome di colonna originariamente sketchato. Estratto `User::acceptedFriendIds()` da `FeedController` (duplicazione reale, non prematura: serviva identica anche per decidere chi notificare) e riusato in entrambi. Notifiche inviate **sincrone**, non in coda (`ShouldQueue` deliberatamente non implementata): l'infra di deploy attuale ha solo `php artisan serve`, nessun worker — una notifica in coda senza worker resterebbe bloccata per sempre in `jobs`. Frontend: passata la strategia di `vite-plugin-pwa` da `generateSW` a `injectManifest` con service worker custom (`src/sw.ts`, gestisce `push`/`notificationclick`, precache dell'app shell via `workbox-precaching`) — necessario perché `generateSW` non permette di aggiungere event listener custom al service worker generato. `src/sw.ts` escluso dal typecheck di `vue-tsc` (`tsconfig.app.json`): i tipi `ServiceWorkerGlobalScope` confliggono con la lib `dom` usata dal resto dell'app nello stesso programma TypeScript. `VITE_VAPID_PUBLIC_KEY` esposta nel bundle frontend deliberatamente: è la chiave pubblica, non un segreto. 9 feature test nuovi (83 totali backend: 6 sulle subscription, 3 sulle notifiche — `Notification::fake()` per verificare chi viene notificato, senza inviare davvero push a endpoint finti). **Non verificato**: la ricezione reale della notifica in un browser vero (richiede un dispositivo/browser che parli con un vero push service, non simulabile da qui) — verificato solo fino al livello "il backend crea/invia correttamente la notifica al canale WebPush", il resto (consegna FCM/Mozilla → OS → utente) va provato manualmente.
- **2026-08-29 — Fase 8, Polish (prima tranche)**: AGENTS.md non ha una sezione dedicata per la Fase 8 (a differenza delle altre 7), quindi lo scope è stato scelto insieme all'utente invece di indovinarlo, a partire dai punti "rimandati per dopo" annotati nelle decisioni precedenti. Tre interventi: (1) **UI di modifica serie**: `WorkoutDetailView.vue` ora supporta la modifica in-place di una serie già loggata (`PATCH /api/v1/workout-sets/{id}`, già esposto dal backend dalla Fase 4 ma mai usato dal frontend) invece del solo cancella+ri-aggiungi — un set alla volta in modifica (`editingSetId`), stesso pattern a 4 campi del form di log. (2) **Rimossa (poi ripristinata) la route custom `GET /up`** — vedi voce dedicata sotto per il perché era sbagliato rimuoverla. (3) **`VAPID_SUBJECT` reale**: impostato a `mailto:mrghettone@gmail.com` in `backend/.env` locale (mai committato, resta `.env`); `.env.example` resta con il placeholder generico `admin@example.com`, come deve essere per un file di template committato.
- **2026-08-30 — Errore mio: rimuovere `GET /up` custom rompeva davvero Railway**: la rimozione in Fase 8 era basata solo su una verifica locale (`php artisan serve` + curl, → 200) — non su un deploy Docker reale, che si comporta diversamente. Causa reale, trovata dopo la segnalazione dell'utente: `storage/framework/{cache/data,sessions,views}` e `storage/logs` sono cartelle vuote, mai tracciate da git (git non traccia cartelle vuote) e **esplicitamente escluse anche da `backend/.dockerignore`** — quindi nell'immagine Docker non esistono affatto. La route nativa `health: '/up'` di Laravel renderizza una vista Blade (`health-up.blade.php`), e Blade **deve** scrivere la cache compilata in `storage/framework/views/`: senza quella cartella, il rendering fallisce con un errore imprevisto in un contesto (Docker) diverso da quello testato (locale, dove le cartelle esistevano già su disco). La route custom (`response()->json(...)`, nessuna vista Blade) bypassa del tutto questo problema — è per questo che "funzionava" mentre quella nativa no, non per un caso o una preferenza di formato. **Fix applicato in due parti**: (a) ripristinata la route custom in `routes/web.php` (identica a prima, l'utente l'ha chiesto esplicitamente e resta la soluzione più semplice/verificata); (b) **causa radice sistemata comunque** in `backend/Dockerfile` (`RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs && chmod -R 775 storage bootstrap/cache` dopo `COPY . .`), perché lo stesso problema avrebbe rotto silenziosamente **qualunque** rendering di vista Blade in produzione — comprese le pagine di errore Laravel quando `APP_DEBUG=false`, non solo `/up`. Non verificabile localmente con un build Docker reale (Docker non installato su questa macchina): il fix (a) è già confermato funzionante da Railway stesso (era così fino alla Fase 8), il fix (b) è standard/ben noto per deploy Laravel via Docker ma va confermato al prossimo deploy reale. **Lezione**: un "cleanup" che sembra innocuo verificato solo in locale (`php artisan serve`) non basta quando il comportamento dipende dall'ambiente di produzione (qui: la porzione di filesystem effettivamente copiata nell'immagine Docker) — se una modifica tocca qualcosa che si comporta diversamente in Docker (route nativa vs custom, filesystem, permessi), va segnalato come "non completamente verificato" invece di dichiararlo risolto.
