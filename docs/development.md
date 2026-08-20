# Development

## Requisiti

- PHP **8.3+** (Laravel 13 richiede PHP ^8.3 — vincolo reale di sintassi del framework, non solo dichiarato in `composer.json`)
- Composer 2.x
- Node.js 20+ / npm
- MySQL o MariaDB

## Setup backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Setup frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Comandi utili

Backend:

```bash
php artisan test          # test suite
php artisan migrate       # applicare migration
php artisan migrate:fresh --seed   # reset + seed (quando disponibili seeder)
```

Frontend:

```bash
npm run build     # type-check (vue-tsc) + build produzione
npm run dev        # dev server
```

## Stato ambiente locale noto (2026-08-20)

Sulla macchina di sviluppo usata per l'inizializzazione del progetto:

- PHP installato: 8.2.30 → **incompatibile** con Laravel 13. `php artisan` termina con errore di parsing su sintassi PHP 8.3. Necessario aggiornare PHP a 8.3+ prima di poter eseguire qualsiasi comando artisan (migrate, serve, test, key:generate, ecc.).
- Nessun server MySQL/MariaDB rilevato in locale.
- Il frontend (Vue/Vite) funziona correttamente con Node 24 / npm 11: `npm run build` e `npm run dev` verificati con successo.

Il backend è stato comunque scaffoldato con successo (`composer install` completato tramite `--ignore-platform-reqs`); manca solo un runtime PHP 8.3+ per eseguirlo.
