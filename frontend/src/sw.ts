// Service worker custom (strategia injectManifest di vite-plugin-pwa):
// serve solo per gestire push/notificationclick, il precache dell'app shell
// resta a workbox. Escluso dal typecheck di vue-tsc (tsconfig.app.json):
// ServiceWorkerGlobalScope confligge con i type "dom" del resto dell'app.
// @ts-nocheck

import { clientsClaim } from 'workbox-core'
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching'

// Con la strategia injectManifest, a differenza di generateSW, vite-plugin-pwa
// NON aggiunge automaticamente skipWaiting/clientsClaim al service worker:
// vanno chiamati esplicitamente qui, altrimenti (bug reale osservato in
// produzione: la grafica non si aggiornava mai) un nuovo deploy resta
// "in attesa" a tempo indeterminato finche' l'utente non chiude *tutte* le
// schede dell'app, perche' e' cosi' che si comporta di default un service
// worker secondo spec. skipWaiting() attiva subito la nuova versione,
// clientsClaim() la fa prendere controllo delle schede gia' aperte senza
// bisogno di un refresh manuale — coerente con registerType: 'autoUpdate'
// in vite.config.ts (aggiornamento silenzioso, nessun prompt all'utente).
self.skipWaiting()
clientsClaim()

cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)

self.addEventListener('push', (event) => {
  const payload = event.data?.json() ?? {}

  event.waitUntil(
    self.registration.showNotification(payload.title ?? 'Gym Bros', {
      body: payload.body,
      icon: payload.icon ?? '/pwa-192x192.png',
      badge: '/pwa-64x64.png',
      data: payload.data ?? {},
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const url = event.notification.data?.url ?? '/'

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      const existing = clients.find((client) => new URL(client.url).pathname === url)
      if (existing) {
        return existing.focus()
      }
      return self.clients.openWindow(url)
    }),
  )
})
