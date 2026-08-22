# Database

## Motore

MySQL / MariaDB, gestito interamente tramite Laravel migrations. Nessuna modifica manuale allo schema: ogni cambiamento passa da una migration.

## Stato attuale (Fase 3)

`users` implementata (migration di scaffold + `add_username_and_avatar_to_users_table` in Fase 2, che rimuove `name` e aggiunge `username`/`avatar`). `friendships` implementata in Fase 3. Le altre tabelle sotto restano lo schema concettuale pianificato per le fasi successive.

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

### `exercises`

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| name | string | |
| description | text, nullable | |
| created_at / updated_at | timestamp | |

### `workouts`

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users | |
| started_at | timestamp | |
| finished_at | timestamp, nullable | |
| status | enum | `active`, `completed`, `cancelled` |
| created_at / updated_at | timestamp | |

Vincolo applicativo: un utente ha al massimo un workout `active` alla volta.

### `workout_exercises` (pivot)

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| workout_id | FK → workouts | |
| exercise_id | FK → exercises | |
| order | integer | ordine nell'allenamento |
| created_at / updated_at | timestamp | |

### `workout_sets`

| campo | tipo | note |
|---|---|---|
| id | bigint PK | |
| workout_exercise_id | FK → workout_exercises | |
| set_number | integer | |
| weight | decimal, nullable | |
| repetitions | integer, nullable | |
| duration | integer, nullable | secondi, per esercizi a tempo |
| distance | decimal, nullable | per esercizi cardio |
| created_at / updated_at | timestamp | |

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
