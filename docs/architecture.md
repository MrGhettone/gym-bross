# Architettura

## Panoramica

Gym Bros è composto da due progetti indipendenti che comunicano solo via API REST JSON:

```
┌─────────────────────┐        HTTPS / JSON        ┌──────────────────────┐
│   frontend (Vue)     │  ───────────────────────▶  │   backend (Laravel)  │
│   Vite + TS + Pinia  │  ◀───────────────────────  │   API REST /api/v1   │
│   PWA / Service Wkr  │      Authorization: Bearer  │   MySQL/MariaDB      │
└─────────────────────┘        (Sanctum, Fase 2)    └──────────────────────┘
```

- Il frontend non accede mai direttamente al database.
- Il backend è l'unica fonte autorevole per autenticazione, autorizzazione, validazione, stato e privacy.
- Il client Vue non è mai considerato trusted: ogni controllo di sicurezza è duplicato (o meglio, applicato solo) lato server.

## Perché questo stack

- **Laravel 12**: framework maturo, batterie incluse (auth, validation, ORM, notifications, Sanctum), evita di reinventare autenticazione/autorizzazione. Scelto (invece di Laravel 13) per allinearsi a PHP ^8.2, la versione reale disponibile sulla macchina di sviluppo.
- **Sanctum a token Bearer**: frontend e backend sono su domini diversi in produzione (nessun dominio condiviso), quindi l'auth cookie-based di Sanctum non è utilizzabile (un cookie del backend non è leggibile via JS da un altro dominio) — vedi [docs/authentication.md](authentication.md). Preferito comunque a una soluzione JWT custom: Sanctum gestisce già creazione/revoca token in modo semplice, sufficiente per questo caso d'uso.
- **Vue 3 + Vite + TS**: stack frontend leggero, tipizzato, con ottimo supporto PWA tramite `vite-plugin-pwa` (Fase 6).
- **Nessun WebSocket/real-time in Fase 1-5**: il feed usa richieste HTTP normali; broadcasting/Reverb valutati solo se necessario in futuro.

## Comunicazione frontend↔backend

- API versionate: `/api/v1/*`.
- CORS: origini esplicite da env (`FRONTEND_URL`), `supports_credentials=false` (nessun cookie coinvolto, auth via header `Authorization`).
- Formato risposta coerente: `{ "data": ... }` per successo, `{ "message": "..." }` per errore (con `errors` per la validazione 422).

## Stato attuale (Fase 3)

- Scaffolding backend/frontend completato (Fase 1).
- Autenticazione Sanctum a token Bearer implementata: register/login/logout/me, form Vue mobile-first, route guard lato frontend. Dettagli in [docs/authentication.md](authentication.md).
- Amicizie implementate: richiesta/accetta/rifiuta/rimuovi/blocca, `FriendshipPolicy` per l'autorizzazione, view Vue mobile-first. Dettagli in [docs/api.md](api.md) e [docs/database.md](database.md).
- Workout, feed, notifiche non ancora implementati.
