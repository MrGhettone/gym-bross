# Notifiche

Non ancora implementate (pianificate per Fase 7). Documento di riferimento per la direzione architetturale.

## Tecnologia

Web Push standard del browser:

- Service Worker (frontend)
- Push API + Notification API (frontend)
- VAPID (backend, chiavi via env: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`)
- Libreria PHP Web Push lato Laravel — da scegliere in Fase 7 verificando compatibilità con Laravel 13/PHP 8.3+ e manutenzione attiva. Non implementare la crittografia Web Push a mano.

## Flusso previsto

1. Il frontend chiede il permesso di notifica al browser.
2. Il Service Worker genera una `PushSubscription`.
3. Il frontend invia la subscription al backend (`POST /api/v1/notifications/subscriptions` o simile).
4. Il backend salva la subscription in `push_subscriptions`, associata all'utente.
5. Al verificarsi di un evento notificabile, il backend invia una Web Push alla subscription tramite la libreria scelta.

## Eventi notificabili (MVP)

- Amico ha iniziato un workout
- Amico ha terminato un workout
- (eventualmente) amico ha raggiunto un nuovo record personale

Da mantenere minimale: non bombardare l'utente di notifiche. Le notifiche devono essere configurabili dall'utente (preferenze, Fase 7/8).

## Sicurezza

- Chiavi VAPID solo in env, mai committate.
- Nessun dato sensibile nel payload della notifica oltre al necessario.
