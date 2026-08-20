# Development

## Requisiti

- PHP **8.4.1+** (Laravel 13 richiede PHP ^8.3 in `composer.json`, ma il `composer.lock` committato ha bloccato pacchetti Symfony che richiedono PHP ≥8.4.1 — vedi nota sotto. L'immagine Docker di produzione usa `php:8.4-cli-bookworm`, quindi conviene allinearsi anche in locale)
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

- PHP installato: 8.2.30 → **incompatibile** con Laravel 13. `php artisan` termina con errore di parsing su sintassi PHP 8.3.
- Il backend è stato scaffoldato con `composer create-project ... --ignore-platform-reqs` (necessario per bypassare il controllo PHP 8.2 in fase di scaffold). Questo ha però fatto sì che Composer, senza vincoli di piattaforma da rispettare, bloccasse nel `composer.lock` versioni di Symfony (8.1.x) che richiedono **PHP ≥8.4.1** — un requisito più stretto di quanto dichiarato in `composer.json` (`^8.3`). Il deploy su Railway ha fallito la prima volta proprio per questo (immagine Docker su PHP 8.3), risolto allineando l'immagine a `php:8.4-cli-bookworm`.
- Di conseguenza, per eseguire `composer install` (non solo `composer update`) su questo lock file serve **PHP 8.4.1+** anche in locale, non semplicemente 8.3+.
- Nessun server MySQL/MariaDB rilevato in locale.
- Il frontend (Vue/Vite) funziona correttamente con Node 24 / npm 11: `npm run build` e `npm run dev` verificati con successo.

Se in futuro si vuole tornare a supportare PHP 8.3 puro in locale, occorre rigenerare il lock file con `composer update` da una macchina/container con PHP 8.3 (non 8.2), così Composer risolve versioni Symfony compatibili con 8.3 invece delle 8.1.x attuali.
