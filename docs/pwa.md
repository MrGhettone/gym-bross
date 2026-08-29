# PWA

Implementata in Fase 6.

## Obiettivo

Un'unica codebase web (`frontend/`) installabile su Home screen su Android, iOS/iPadOS e desktop, senza app native separate.

## Componenti implementati

- **Manifest** (`manifest.webmanifest`, generato da `vite-plugin-pwa` in build): nome "Gym Bros", `display: standalone`, `theme_color: #aa3bff` (stesso accent del resto dell'app), `background_color: #ffffff`, `start_url`/`scope: /`, `lang: it`.
- **Service Worker**: generato da `vite-plugin-pwa` (modalità `generateSW`, precache automatico dell'app shell — JS/CSS/HTML/icone del build). `registerType: autoUpdate` — nessun prompt "nuova versione disponibile", si aggiorna in background.
- **Icone**: generate una tantum da `frontend/public/favicon.svg` con `@vite-pwa/assets-generator` (`pwa-64x64.png`, `pwa-192x192.png`, `pwa-512x512.png`, `apple-touch-icon-180x180.png`, `favicon.ico`) + `maskable-icon-512x512.png` generata a parte da una sorgente dedicata (`frontend/src/assets/pwa-maskable-source.svg`, sfondo pieno senza angoli arrotondati: il preset "minimal" applicava un padding aggiuntivo attorno alle icone `any`, con angoli già arrotondati nell'SVG, che con una maschera circolare del sistema avrebbe mostrato bordi bianchi). Vedi "Rigenerare le icone" sotto.
- **Caching**: solo l'app shell è precachata (`globPatterns` di default). **Nessuna regola `runtimeCaching` per `/api/*`**: le chiamate API vanno sempre in rete, mai in cache — deliberato, coerente con l'obiettivo di non introdurre caching aggressivo dei dati.
- **Gestione offline**: nessuna esperienza offline-first. Lo stato "Backend: offline" già presente in `HomeView.vue` dalla Fase 1 (basato su `GET /api/v1/ping`) resta il segnale che l'utente vede quando l'API non è raggiungibile.

## Rigenerare le icone

`@vite-pwa/assets-generator` **non è una dipendenza permanente** del progetto (dipende da `sharp`, che porta una CVE nota — vedi decisione in [AGENTS.md](../AGENTS.md#decisioni-architetturali--problemi-aperti)). Per rigenerare le icone se cambia il logo:

```bash
cd frontend
npm install -D @vite-pwa/assets-generator
npx pwa-assets-generator --preset minimal public/favicon.svg
# rigenera anche la maskable da sorgente dedicata (sfondo pieno):
node -e "require('sharp')('src/assets/pwa-maskable-source.svg', {density:384}).resize(512,512).png().toFile('public/maskable-icon-512x512.png')"
npm uninstall @vite-pwa/assets-generator
```

## Note iOS

- Le Web Push su iOS/iPadOS richiedono l'installazione della PWA sulla Home screen (non funzionano da Safari in tab, nelle versioni iOS che le supportano) — rilevante per la Fase 7.
- iOS non legge il web manifest per l'icona/nome della Home screen: `index.html` ha i meta tag `apple-touch-icon`, `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `apple-mobile-web-app-title` espliciti.
- `viewport-fit=cover` già presente dalla Fase 1 per la safe-area.

## Verifica

`npm run build` genera `dist/manifest.webmanifest`, `dist/sw.js`, `dist/registerSW.js`. Verificato servendo `dist/` con `npm run preview`: manifest (`content-type: application/manifest+json`), service worker e icone rispondono `200`. Il Service Worker è attivo solo in build di produzione, non in `npm run dev` (comportamento di default di `vite-plugin-pwa`, nessuna configurazione `devOptions` aggiunta).
