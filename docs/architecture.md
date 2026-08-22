# Architettura

## Panoramica

Gym Bros è composto da due progetti indipendenti che comunicano solo via API REST JSON:

```
┌─────────────────────┐        HTTPS / JSON        ┌──────────────────────┐
│   frontend (Vue)     │  ───────────────────────▶  │   backend (Laravel)  │
│   Vite + TS + Pinia  │  ◀───────────────────────  │   API REST /api/v1   │
│   PWA / Service Wkr  │        cookie di sessione   │   MySQL/MariaDB      │
└─────────────────────┘        (Sanctum, Fase 2)    └──────────────────────┘
```

- Il frontend non accede mai direttamente al database.
- Il backend è l'unica fonte autorevole per autenticazione, autorizzazione, validazione, stato e privacy.
- Il client Vue non è mai considerato trusted: ogni controllo di sicurezza è duplicato (o meglio, applicato solo) lato server.

## Perché questo stack

- **Laravel 12**: framework maturo, batterie incluse (auth, validation, ORM, notifications, Sanctum), evita di reinventare autenticazione/autorizzazione. Scelto (invece di Laravel 13) per allinearsi a PHP ^8.2, la versione reale disponibile sulla macchina di sviluppo.
- **Sanctum (pianificato)**: adatto a un'architettura SPA/PWA con frontend e backend collegati in modo stretto; evita la complessità di JWT quando non è necessaria.
- **Vue 3 + Vite + TS**: stack frontend leggero, tipizzato, con ottimo supporto PWA tramite `vite-plugin-pwa` (Fase 6).
- **Nessun WebSocket/real-time in Fase 1-5**: il feed usa richieste HTTP normali; broadcasting/Reverb valutati solo se necessario in futuro.

## Comunicazione frontend↔backend

- API versionate: `/api/v1/*`.
- CORS: origini esplicite da env (`FRONTEND_URL`), `supports_credentials=true` per supportare cookie di sessione.
- Formato risposta coerente: `{ "data": ... }` per successo, `{ "message": "..." }` per errore (con `errors` per la validazione 422).

## Stato attuale (Fase 2)

- Scaffolding backend/frontend completato (Fase 1).
- Autenticazione Sanctum cookie-based implementata: register/login/logout/me, form Vue mobile-first, route guard lato frontend. Dettagli in [docs/authentication.md](authentication.md).
- Amicizie, workout, feed, notifiche non ancora implementati.
