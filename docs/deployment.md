# Deployment — Backend su Railway

Il backend Laravel è pensato per essere ospitato su Railway (piano free). Questo documento descrive la configurazione necessaria lato Railway e cosa è già predisposto nel repository.

## File di configurazione nel repo

- `backend/start.sh` — script di avvio: esegue le migration (`php artisan migrate --force`) e poi avvia il server PHP integrato (`php artisan serve`) sull'host/porta forniti da Railway. Adeguato per il piano free (nessun bisogno di Nginx/PHP-FPM).
- `backend/railway.json` — config-as-code per Railway: build command (`composer install --no-dev --optimize-autoloader`), start command (`bash start.sh`), healthcheck su `/up` (route di health-check nativa di Laravel, già registrata in `bootstrap/app.php`).

## Passaggi da fare nella dashboard Railway (non automatizzabili da qui)

### 1. Root Directory del servizio

Il repository è un monorepo (`backend/` + `frontend/`). Railpack analizza di default la **root del repo**, dove non trova né `composer.json` né `artisan` (sono dentro `backend/`) — è la causa esatta dell'errore "Railpack could not determine how to build the app".

Da fare **una sola volta**, sul servizio backend:

> Project → Service → **Settings → Source → Root Directory** → `backend`

Dopo questo cambio, Railway rilegge automaticamente `backend/railway.json` e `backend/start.sh`.

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

## Troubleshooting: "Railpack could not determine how to build the app" nonostante Root Directory impostato

Se il servizio è stato creato tramite **deploy da Template Railway** (riconoscibile in Settings → Source dalla presenza di "Upstream Repo" con i pulsanti "Eject" / "Check for updates"), impostare Root Directory manualmente potrebbe non bastare: il servizio resta agganciato alla configurazione di build del template, che nei log di Railpack risulta ancora scansionare la root del repo invece di `backend/`.

Fix verificato:

1. Creare un **nuovo servizio** nello stesso progetto Railway con **"+ New" → "GitHub Repo"** (non da Template), selezionando `MrGhettone/gym-bross` direttamente.
2. Sul nuovo servizio: Settings → Source → Root Directory → `backend`.
3. Ricollegare/ricreare le variabili d'ambiente (si può riusare lo stesso plugin MySQL già presente nel progetto, referenziandolo dal nuovo servizio).
4. Deploy del nuovo servizio, poi eliminare il vecchio servizio da Template.

## Limiti noti del piano free

- Filesystem effimero: non fare affidamento su file scritti su disco tra un deploy e l'altro (per questo si usa `SESSION_DRIVER=database` invece di `file`, e `CACHE_STORE=database` invece di `file`).
- Il servizio può andare in sleep/riavviarsi per inattività a seconda dei limiti del piano: la prima richiesta dopo un periodo di inattività può essere più lenta.
- `php artisan serve` è il server di sviluppo integrato di PHP: sufficiente per un MVP a basso traffico sul piano free, ma non è pensato per carichi di produzione elevati. Da rivalutare (es. PHP-FPM + Nginx via Dockerfile) se il traffico cresce.

## Cosa NON è ancora gestito

- Deploy del frontend (non ancora deciso dove ospitarlo — puà essere un altro servizio Railway a sé stante come static site, o altrove).
- VAPID keys per Web Push: da impostare come variabili Railway solo in Fase 7.
