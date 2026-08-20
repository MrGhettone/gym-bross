# Deployment — Backend su Railway

Il backend Laravel è pensato per essere ospitato su Railway (piano free). Questo documento descrive la configurazione necessaria lato Railway e cosa è già predisposto nel repository.

## Perché Dockerfile e non il builder automatico (Railpack)

Il repository è un monorepo (`backend/` + `frontend/`). In teoria Railway supporta questo caso impostando "Root Directory" sul servizio; in pratica, su questo progetto, la fase di rilevamento automatico di Railpack ("prepare") ha continuato a scansionare la **root dell'intero repo** invece di `backend/`, sia su un servizio creato da Template sia su un servizio creato collegando direttamente il repo GitHub — cioè Root Directory non veniva applicato in tempo per quella fase (comportamento non affidabile lato Railway/Railpack, non un errore nel repo).

Per eliminare questa ambiguità, il backend usa un **Dockerfile esplicito**: Railway, quando il builder è impostato su Dockerfile, non esegue più il rilevamento euristico di Railpack — costruisce semplicemente l'immagine secondo le istruzioni scritte nel Dockerfile, in modo deterministico.

## File di configurazione nel repo

- `backend/Dockerfile` — immagine PHP 8.4 CLI (il `composer.lock` committato ha bloccato pacchetti Symfony che richiedono PHP ≥8.4.1, vedi troubleshooting sotto), installa le estensioni necessarie (pdo_mysql, mbstring, xml, zip), esegue `composer install`, poi avvia `bash start.sh`.
- `backend/.dockerignore` — esclude `.git`, `vendor/`, `.env`, log/cache locali dal contesto di build.
- `backend/start.sh` — script di avvio del container: esegue le migration (`php artisan migrate --force`) e poi avvia il server PHP integrato (`php artisan serve`) sull'host/porta forniti da Railway. Adeguato per il piano free (nessun bisogno di Nginx/PHP-FPM separati).
- `backend/railway.json` — config-as-code per Railway: `builder: DOCKERFILE`, `dockerfilePath: Dockerfile`, healthcheck su `/up` (route di health-check nativa di Laravel, già registrata in `bootstrap/app.php`).

## Passaggi da fare nella dashboard Railway (non automatizzabili da qui)

### 1. Root Directory e Builder del servizio

Da fare **una sola volta**, sul servizio backend:

> Project → Service → **Settings → Source → Root Directory** → `backend`

Poi, in **Settings → Build**, verificare/impostare esplicitamente:

- **Builder** → `Dockerfile` (se il servizio mostra ancora "Railpack" scelto automaticamente, cambiarlo a mano — non fidarsi solo del `railway.json` committato, dato che finora Railway non ha sempre riletto correttamente la configurazione del repo per questo progetto).
- **Dockerfile Path** → se il campo è relativo alla *root del repo* (non a Root Directory), usare `backend/Dockerfile`; se invece è già relativo a Root Directory, basta `Dockerfile`. Provare un valore e controllare nel log di build quale file viene effettivamente letto.

Se dopo questo cambio il log di build mostra ancora l'analisi Railpack (`prepare railpack-...`) invece di step Docker (`FROM php:8.3...`, `RUN apt-get...`), vuol dire che il builder non è stato applicato: ricontrollare il campo Builder nelle impostazioni.

### 2. Database MySQL

Aggiungere un plugin **MySQL** al progetto Railway (New → Database → MySQL). Railway espone variabili tipo `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` (nomi esatti visibili nella tab Variables del plugin).

Nel servizio backend, impostare (Variables tab), usando i reference al plugin MySQL invece di valori fissi:

```
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

(I nomi delle variabili di reference vanno verificati nella tab "Variables" del plugin MySQL creato, possono differire leggermente in base alla versione del plugin.)

### 3. Variabili d'ambiente del backend

Da impostare come Variables del servizio (mai nel repo):

```
APP_NAME=Gym Bros
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<dominio-pubblico-railway>
FRONTEND_URL=https://<dominio-produzione-frontend>

APP_KEY=<generata localmente, vedi sotto>

SESSION_DRIVER=database
SESSION_DOMAIN=null
CACHE_STORE=database
QUEUE_CONNECTION=database
```

`APP_KEY` non può essere generata a runtime su Railway: il filesystem è effimero e `php artisan key:generate` scriverebbe su un `.env` che non persiste tra i deploy. Generarla **una volta in locale** (con PHP 8.3+) e incollarla come variabile:

```bash
php artisan key:generate --show
```

Copiare il valore restituito (es. `base64:...`) nella variabile `APP_KEY` su Railway.

### 4. CORS

`FRONTEND_URL` (già usata in `backend/config/cors.php`) deve puntare al dominio di produzione reale del frontend, non a `localhost`, altrimenti le richieste dal frontend deployato verranno bloccate dal CORS.

## Troubleshooting

### "Railpack could not determine how to build the app" / "Script start.sh not found"

Sintomo: il log di build mostra `prepare railpack-...` e un albero file che è la **root del repo** (`backend/`, `docs/`, `frontend/`, ...) invece del contenuto di `backend/`.

Osservato su questo progetto sia su un servizio creato da **Template Railway** (riconoscibile da "Upstream Repo" / "Eject" / "Check for updates" in Settings → Source) sia su un servizio creato collegando **direttamente** il repo GitHub con Root Directory=`backend` già impostato — in entrambi i casi Railpack ha continuato a scansionare la root dell'intero repo. Root Directory da solo non è quindi sufficiente in modo affidabile per questo progetto.

Fix: passare al builder **Dockerfile** (vedi sezione sopra), che bypassa del tutto la fase di rilevamento automatico di Railpack. Se il problema persiste anche con Dockerfile, il prossimo sospetto è il campo **Dockerfile Path** nelle impostazioni del servizio: va verificato se è relativo alla root del repo o a Root Directory (i due comportamenti richiedono un valore diverso, vedi sopra).

### Il servizio era da Template e non risponde ai cambi di Root Directory/Builder

Se cambiare Root Directory e Builder sullo stesso servizio non ha effetto sui log di build, creare un **servizio nuovo** con **"+ New" → "GitHub Repo"** (non da Template) selezionando `MrGhettone/gym-bross` direttamente, ripetere la configurazione (Root Directory, Builder Dockerfile, variabili d'ambiente — si può riusare lo stesso plugin MySQL del progetto), fare il deploy sul nuovo servizio ed eliminare quello vecchio una volta verificato che funziona.

### `composer install` fallisce con "requires php >=8.4.1"

Sintomo: il build Docker arriva a eseguire `composer install`, ma fallisce con una lista di "Problem N" su pacchetti `symfony/*` che richiedono `php >=8.4.1`, mentre `composer.json` dichiara solo `php: ^8.3`.

Causa: il `composer.lock` è stato generato in locale con `--ignore-platform-reqs` (necessario perché la macchina di sviluppo aveva PHP 8.2). Senza controlli di piattaforma, Composer ha bloccato versioni Symfony (8.1.x) più recenti di quelle compatibili con PHP 8.3, che richiedono 8.4.1+. Il `Dockerfile` usa quindi `php:8.4-cli-bookworm` per soddisfare questo vincolo effettivo, non `php:8.3`.

Se in futuro serve tornare a PHP 8.3 (in produzione o in locale), rigenerare `composer.lock` con `composer update` da un ambiente con PHP 8.3 reale (non 8.2, non `--ignore-platform-reqs`), così Composer risolve versioni Symfony compatibili con 8.3.

## Limiti noti del piano free

- Filesystem effimero: non fare affidamento su file scritti su disco tra un deploy e l'altro (per questo si usa `SESSION_DRIVER=database` invece di `file`, e `CACHE_STORE=database` invece di `file`).
- Il servizio può andare in sleep/riavviarsi per inattività a seconda dei limiti del piano: la prima richiesta dopo un periodo di inattività può essere più lenta.
- `php artisan serve` è il server di sviluppo integrato di PHP: sufficiente per un MVP a basso traffico sul piano free, ma non è pensato per carichi di produzione elevati. Da rivalutare (es. PHP-FPM + Nginx via Dockerfile) se il traffico cresce.

## Cosa NON è ancora gestito

- Deploy del frontend (non ancora deciso dove ospitarlo — puà essere un altro servizio Railway a sé stante come static site, o altrove).
- VAPID keys per Web Push: da impostare come variabili Railway solo in Fase 7.
