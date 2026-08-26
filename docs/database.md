# Database

## Motore

MySQL / MariaDB, gestito interamente tramite Laravel migrations. Nessuna modifica manuale allo schema: ogni cambiamento passa da una migration.

## Stato attuale (Fase 4)

`users` implementata (migration di scaffold + `add_username_and_avatar_to_users_table` in Fase 2, che rimuove `name` e aggiunge `username`/`avatar`). `friendships` implementata in Fase 3. `exercises`, `workouts`, `workout_exercises`, `workout_sets` implementate in Fase 4. Resta pianificata solo `push_subscriptions` (Fase 7).

## Schema pianificato

### `users` ✅

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| username | string | univoco |
| email | string | univoco |
| password | string | hashed (Laravel hashing nativo) |
| avatar | string, nullable | |
| created_at / updated_at | timestamp | |

### `friendships` ✅

Rappresenta una relazione tra due utenti con uno stato:

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| requester_id | FK → users, cascade on delete | chi ha inviato la richiesta |
| addressee_id | FK → users, cascade on delete | chi la riceve |
| status | enum | `pending`, `accepted`, `rejected`, `blocked` |
| created_at / updated_at | timestamp | |

Vincoli: unique DB su `(requester_id, addressee_id)` (stessa direzione non duplicabile); la direzione opposta (B→A quando A→B esiste già, in qualsiasi stato) è bloccata a livello applicativo in `StoreFriendshipRequest` — non esprimibile con un vincolo DB senza una colonna calcolata. Nessuna richiesta a se stessi.

### `exercises` ✅

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| name | string, unique | catalogo condiviso, non per-utente |
| description | text, nullable | |
| created_at / updated_at | timestamp | |

Nessun `user_id`: catalogo globale, qualsiasi utente autenticato può crearne di nuovi (validazione: nome univoco). Nessuna route di update/delete in questa fase — evita il problema "chi può modificare/cancellare l'esercizio di qualcun altro" quando non c'è ownership; se un giorno serve, richiederà una decisione esplicita (es. solo admin, o solo chi l'ha creato con un `created_by`).

### `workouts` ✅

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users, cascade on delete | |
| started_at | timestamp | |
| finished_at | timestamp, nullable | |
| status | enum | `active`, `completed`, `cancelled` |
| created_at / updated_at | timestamp | |

Vincolo applicativo (non esprimibile come unique DB standard su MySQL/MariaDB senza colonna generata): un utente ha al massimo un workout `active` alla volta, verificato in `StoreWorkoutRequest`.

### `workout_exercises` (pivot) ✅

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| workout_id | FK → workouts, cascade on delete | |
| exercise_id | FK → exercises, **restrict** on delete | un esercizio referenziato dallo storico non può essere cancellato |
| order | integer | ordine nell'allenamento, assegnato automaticamente (max+1) |
| created_at / updated_at | timestamp | |

### `workout_sets` ✅

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| workout_exercise_id | FK → workout_exercises, cascade on delete | |
| set_number | integer | assegnato automaticamente (max+1) |
| weight | decimal(6,2), nullable | |
| repetitions | integer, nullable | |
| duration | integer, nullable | secondi, per esercizi a tempo |
| distance | decimal(8,2), nullable | per esercizi cardio |
| created_at / updated_at | timestamp | |

Vincolo applicativo: almeno uno tra `weight`/`repetitions`/`duration`/`distance` deve essere presente (nessun set completamente vuoto).

### `push_subscriptions`

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| endpoint | string | |
| p256dh | string | chiave pubblica subscription |
| auth_token | string | |
| created_at / updated_at | timestamp | |

Struttura esatta da confermare in base alla libreria Web Push scelta in Fase 7.

## Convenzioni

- Foreign key con `onDelete` esplicito (cascade/restrict) coerente col comportamento desiderato.
- Indici su colonne usate in filtri frequenti (es. `user_id`, `status`).
- Unique constraint dove richiesto (username, email, coppie friendship).
