# Notifiche

Implementate in Fase 7.

## Tecnologia

Web Push standard del browser:

- Service Worker custom (`frontend/src/sw.ts`, strategia `injectManifest` di `vite-plugin-pwa`): gestisce gli eventi `push` e `notificationclick`, oltre al precache dell'app shell (delegato a `workbox-precaching`).
- Push API + Notification API (frontend).
- VAPID (backend, chiavi via env: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`). Generate con `php artisan webpush:vapid` (su Windows/XAMPP potrebbe servire impostare `OPENSSL_CONF` — vedi AGENTS.md).
- Libreria PHP: `laravel-notification-channels/webpush` (canale di notifica Laravel su `minishlink/web-push`) — gestisce la crittografia Web Push, non implementata a mano.

## Flusso implementato

1. Il frontend chiede il permesso di notifica al browser (`Notification.requestPermission()`, `notifications.service.ts#enable`).
2. Il Service Worker (già registrato per la PWA, Fase 6) genera una `PushSubscription` (`pushManager.subscribe()`, chiave pubblica VAPID passata come `applicationServerKey`).
3. Il frontend invia la subscription al backend: `POST /api/v1/push-subscriptions` (`endpoint`, `keys.p256dh`, `keys.auth`).
4. Il backend salva/aggiorna la subscription in `push_subscriptions` (associata all'utente tramite relazione polimorfica del pacchetto — vedi [docs/database.md](database.md)).
5. Quando un utente avvia o termina un workout, il backend invia una `WorkoutActivityNotification` (canale `WebPushChannel`) a tutti i suoi amici con relazione `accepted` (`User::acceptedFriendIds()`).

## Eventi notificabili (implementati)

- Amico ha iniziato un workout
- Amico ha terminato un workout

Non implementato: notifica per nuovo record personale (non nello scope MVP), preferenze utente per disattivare selettivamente i singoli eventi (per ora è un unico switch on/off in `HomeView.vue` che abilita/disabilita tutte le notifiche push per il browser corrente — un affinamento a eventi selezionabili è rimandato a un'eventuale Fase 8).

## Invio sincrono, non in coda

Le notifiche **non** implementano `ShouldQueue`: vengono inviate in modo sincrono nella stessa richiesta HTTP che avvia/termina il workout. L'infrastruttura di deploy attuale (Railway, piano free) esegue solo `php artisan serve`, nessun worker di coda dedicato — mettere le notifiche in coda senza un worker che le processi le lascerebbe bloccate indefinitamente nella tabella `jobs`. Accettabile per il volume atteso (notifiche solo tra amici, non broadcast). Da rivedere se in futuro si aggiunge un processo worker dedicato.

## Sicurezza

- Chiavi VAPID solo in env, mai committate (`VAPID_PRIVATE_KEY` in particolare).
- `VITE_VAPID_PUBLIC_KEY` (frontend) è la sola chiave **pubblica**: nessun problema a comparire nel bundle JS distribuito al browser, è il suo scopo.
- Nessun dato sensibile nel payload della notifica: solo username di chi si allena, uno stato (iniziato/completato), e l'URL del workout per il click-through.
