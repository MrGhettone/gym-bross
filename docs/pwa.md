# PWA

Non ancora implementata (pianificata per Fase 6). Documento di riferimento per la direzione architetturale.

## Obiettivo

Un'unica codebase web (`frontend/`) installabile su Home screen su Android, iOS/iPadOS e desktop, senza app native separate.

## Componenti pianificati

- **Manifest** (`manifest.webmanifest`): nome, icone, `display: standalone`, `theme_color`, `background_color`.
- **Service Worker**: generato tramite `vite-plugin-pwa` (non ancora installato — verrà aggiunto in Fase 6 per rispettare lo sviluppo incrementale a fasi).
- **Icone**: set multi-risoluzione per Android/iOS/desktop.
- **Caching**: strategia ragionevole per asset statici (app shell), nessun caching aggressivo dei dati API in Fase 6 iniziale.
- **Gestione offline**: messaggio/stato chiaro quando l'API non è raggiungibile, non un'esperienza offline-first completa in MVP.

## Note iOS

- Le Web Push su iOS/iPadOS richiedono l'installazione della PWA sulla Home screen (non funzionano da Safari in tab, nelle versioni iOS che le supportano).
- Verificare meta tag `apple-mobile-web-app-*` quando si implementa il manifest.

## Stato attuale (Fase 1)

- `vite-plugin-pwa` non installato.
- Nessun manifest, Service Worker o icona ancora presenti.
- `index.html` ha già `viewport-fit=cover` per compatibilità con safe-area su iOS.
