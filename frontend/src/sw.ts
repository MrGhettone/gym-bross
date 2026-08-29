// Service worker custom (strategia injectManifest di vite-plugin-pwa):
// serve solo per gestire push/notificationclick, il precache dell'app shell
// resta a workbox. Escluso dal typecheck di vue-tsc (tsconfig.app.json):
// ServiceWorkerGlobalScope confligge con i type "dom" del resto dell'app.
// @ts-nocheck

import { precacheAndRoute } from 'workbox-precaching'

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
