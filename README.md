# Gym Bros

PWA mobile-first che permette a un gruppo di amici di condividere le proprie attività in palestra: allenamenti, esercizi, serie e un feed sociale, con notifiche push del browser.

Stato del progetto: **Fase 1 — Project Setup** completata. Nessuna funzionalità applicativa (auth, amicizie, workout, feed, notifiche) è ancora implementata.

## Stack

- **Frontend**: Vue 3 + Vite + TypeScript, Vue Router, Pinia, PWA (Service Worker, Web Push API) — `/frontend`
- **Backend**: Laravel 13 (PHP), API REST JSON, Eloquent, Sanctum, MySQL/MariaDB — `/backend`
- **Comunicazione**: il frontend parla col backend esclusivamente via API REST JSON versionate (`/api/v1/*`). Il frontend non accede mai direttamente al database.

Dettagli architetturali completi in [AGENTS.md](./AGENTS.md) e in [docs/](./docs).

## Struttura repository

```
gym-bros/
  frontend/   Vue 3 + Vite + TypeScript (PWA)
  backend/    Laravel 13 (API REST)
  docs/       Documentazione tecnica
  README.md
  AGENTS.md
```

## Requisiti locali

- PHP **8.3+** (Laravel 13 richiede PHP ^8.3; vedi nota sotto)
- Composer 2.x
- Node.js 20+ e npm
- MySQL o MariaDB

> ⚠️ **Nota ambiente di sviluppo attuale**: la macchina su cui è stato inizializzato il progetto ha PHP 8.2, che non è compatibile con Laravel 13 (errore di parsing su sintassi PHP 8.3). I comandi `php artisan ...` non funzioneranno finché PHP non viene aggiornato a 8.3+. Il backend è comunque stato scaffoldato correttamente (`composer install` completato). Vedi la sezione "Problemi aperti" più sotto.

## Avvio locale

### Backend

```bash
cd backend
composer install
cp .env.example .env      # già fatto in questo repo, .env non è committato
php artisan key:generate  # richiede PHP 8.3+
php artisan serve
```

L'API sarà disponibile su `http://localhost:8000`. Endpoint di verifica: `GET /api/v1/ping`.

### Frontend

```bash
cd frontend
npm install
npm run dev
```

L'app sarà disponibile su `http://localhost:5173`.

### Variabili d'ambiente

- Backend: copiare `backend/.env.example` in `backend/.env` e configurare `DB_*` per MySQL/MariaDB.
- Frontend: copiare `frontend/.env.example` in `frontend/.env` e impostare `VITE_API_URL`.

Non committare mai i file `.env`.

## Problemi aperti (Fase 1)

1. **PHP locale 8.2 vs Laravel 13 (richiede PHP 8.3+)**: `php artisan` non è eseguibile su questa macchina. Necessario aggiornare PHP prima di procedere con la Fase 2 (migrations, seeders, Sanctum).
2. **Nessun server MySQL/MariaDB locale rilevato**: da installare/configurare prima di eseguire le migration.
3. Sanctum non è ancora installato (verrà aggiunto in Fase 2, insieme all'autenticazione).

## Fasi di sviluppo

Il progetto viene sviluppato per fasi incrementali (vedi [AGENTS.md](./AGENTS.md) per il dettaglio):

1. ✅ Project Setup
2. ⬜ Database + Auth
3. ⬜ Friends
4. ⬜ Workout
5. ⬜ Feed
6. ⬜ PWA
7. ⬜ Web Push
8. ⬜ Polish
