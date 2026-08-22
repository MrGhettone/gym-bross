# Development

## Requisiti

- PHP **8.2+** (Laravel 12 richiede PHP ^8.2 in `composer.json`; l'immagine Docker di produzione usa `php:8.2-cli-bookworm`)
- Composer 2.x
- Node.js **18.17+** (vedi nota sotto: il toolchain frontend è vincolato a Vite 6.x per restare compatibile con Node 18, non richiede Node 20+)
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

## Stato ambiente locale noto (2026-08-22)

Sulla macchina di sviluppo usata per l'inizializzazione del progetto:

- PHP installato: 8.2.12. Il backend è stato inizialmente scaffoldato con Laravel 13 (richiede PHP ^8.3, con `composer.lock` che di fatto bloccava Symfony 8.1.x, cioè PHP ≥8.4.1), incompatibile con questa macchina. Il 2026-08-22 il progetto è stato riportato a **Laravel 12** (richiede solo PHP ^8.2) per allinearsi al PHP locale reale invece di richiedere un aggiornamento PHP — vedi decisione in [AGENTS.md](../AGENTS.md#decisioni-architetturali--problemi-aperti).
- `composer.lock` rigenerato con `composer update` **senza** `--ignore-platform-reqs`, direttamente su PHP 8.2.12, quindi verificato installabile per davvero su questa macchina.
- Nessun server MySQL/MariaDB rilevato in locale.
- Node installato: 18.17.1 / npm 9.6.7. Il frontend era inizialmente scaffoldato con Vite 8 (richiede Node ^20.19.0 || >=22.12.0): `npm run build` falliva subito con `SyntaxError: ... 'node:util' does not provide an export named 'styleText'`, un'API Node introdotta dopo la 18. Il 2026-08-22 il toolchain è stato riportato a **Vite 6** (compatibile con Node ^18.0.0) — vedi decisione in [AGENTS.md](../AGENTS.md#decisioni-architetturali--problemi-aperti). `vue-router` è stato inoltre riportato dalla 5.x (peer dependency opzionale ma conflittuale su `vite`/`pinia`) alla **4.x**, coerente con le API già usate in `src/router/index.ts` (nessuna funzionalità di v5 in uso). `npm run build` e `npm run dev` verificati con successo su questa configurazione.
