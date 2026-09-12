# Deployment — Backend su Railway

Il backend Laravel è pensato per essere ospitato su Railway (piano free). Questo documento descrive la configurazione necessaria lato Railway e cosa è già predisposto nel repository.

## Perché Dockerfile e non il builder automatico (Railpack)

Il repository è un monorepo (`backend/` + `frontend/`). In teoria Railway supporta questo caso impostando "Root Directory" sul servizio; in pratica, su questo progetto, la fase di rilevamento automatico di Railpack ("prepare") ha continuato a scansionare la **root dell'intero repo** invece di `backend/`, sia su un servizio creato da Template sia su un servizio creato collegando direttamente il repo GitHub — cioè Root Directory non veniva applicato in tempo per quella fase (comportamento non affidabile lato Railway/Railpack, non un errore nel repo).

Per eliminare questa ambiguità, il backend usa un **Dockerfile esplicito**: Railway, quando il builder è impostato su Dockerfile, non esegue più il rilevamento euristico di Railpack — costruisce semplicemente l'immagine secondo le istruzioni scritte nel Dockerfile, in modo deterministico.

## File di configurazione nel repo

- `backend/Dockerfile` — immagine PHP 8.2 CLI (coerente con Laravel 12, che richiede PHP ^8.2), installa le estensioni necessarie (pdo_mysql, mbstring, xml, zip, bcmath — quest'ultima per la firma ECDSA delle notifiche Web Push, vedi troubleshooting sotto), esegue `composer install`, ricrea le sottocartelle di `storage/` escluse da `.dockerignore` (vedi sotto), poi avvia `bash start.sh`.
- `backend/.dockerignore` — esclude `.git`, `vendor/`, `.env`, log/cache locali dal contesto di build. **Attenzione**: esclude anche `storage/framework/{cache/data,sessions,views}` e `storage/logs` — cartelle vuote mai tracciate da git, quindi senza il passaggio dedicato nel Dockerfile **non esistono affatto** nell'immagine, e qualunque rendering di vista Blade (comprese le pagine di errore quando `APP_DEBUG=false`) fallisce non riuscendo a scrivere la cache compilata. Vedi troubleshooting sotto.
- `backend/start.sh` — script di avvio del container: esegue le migration (`php artisan migrate --force`) e poi avvia il server PHP integrato (`php artisan serve`) sull'host/porta forniti da Railway. Adeguato per il piano free (nessun bisogno di Nginx/PHP-FPM separati).
- `backend/railway.json` — config-as-code per Railway: `builder: DOCKERFILE`, `dockerfilePath: Dockerfile`, healthcheck su `/up`. **Non** la route di health-check nativa di Laravel: c'è una route custom in `routes/web.php` che risponde con JSON (`response()->json(...)`, nessuna vista Blade) — vedi troubleshooting sotto per il perché.

## Passaggi da fare nella dashboard Railway (non automatizzabili da qui)

### 1. Root Directory e Builder del servizio

Da fare **una sola volta**, sul servizio backend:

> Project → Service → **Settings → Source → Root Directory** → `backend`

Poi, in **Settings → Build**, verificare/impostare esplicitamente:

- **Builder** → `Dockerfile` (se il servizio mostra ancora "Railpack" scelto automaticamente, cambiarlo a mano — non fidarsi solo del `railway.json` committato, dato che finora Railway non ha sempre riletto correttamente la configurazione del repo per questo progetto).
- **Dockerfile Path** → se il campo è relativo alla *root del repo* (non a Root Directory), usare `backend/Dockerfile`; se invece è già relativo a Root Directory, basta `Dockerfile`. Provare un valore e controllare nel log di build quale file viene effettivamente letto.

Se dopo questo cambio il log di build mostra ancora l'analisi Railpack (`prepare railpack-...`) invece di step Docker (`FROM php:8.2...`, `RUN apt-get...`), vuol dire che il builder non è stato applicato: ricontrollare il campo Builder nelle impostazioni.

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

LOG_CHANNEL=stderr
```

`LOG_CHANNEL=stderr` è importante quanto le altre: senza, Laravel scrive i log (eccezioni, `Log::error()`, ...) su `storage/logs/laravel.log`, un file su filesystem effimero che **non compare nel pannello Deploy Logs di Railway** — di fatto invisibile. Con `stderr`, i log vanno sullo stream che Railway effettivamente mostra.

`APP_KEY` non può essere generata a runtime su Railway: il filesystem è effimero e `php artisan key:generate` scriverebbe su un `.env` che non persiste tra i deploy. Generarla **una volta in locale** e incollarla come variabile:

```bash
php artisan key:generate --show
```

Copiare il valore restituito (es. `base64:...`) nella variabile `APP_KEY` su Railway.

### 4. CORS

`FRONTEND_URL` (già usata in `backend/config/cors.php`) deve puntare al dominio di produzione reale del frontend, non a `localhost`, altrimenti le richieste dal frontend deployato verranno bloccate dal CORS.

### 5. VAPID (Web Push, Fase 7)

Backend (Railway → servizio backend → Variables), stessi valori generati in locale (`backend/.env`, mai rigenerarli per ambiente — VAPID è pensato per restare stabile):

```
VAPID_PUBLIC_KEY=<da backend/.env>
VAPID_PRIVATE_KEY=<da backend/.env>
VAPID_SUBJECT=mailto:mrghettone@gmail.com
```

Frontend (Vercel → progetto → **Settings → Environment Variables**): `VITE_VAPID_PUBLIC_KEY` con lo **stesso valore** di `VAPID_PUBLIC_KEY` sopra (è la chiave pubblica, nessun problema a impostarla anche lì). Importante: Vite inietta le variabili `VITE_*` **in fase di build**, non a runtime — dopo averla aggiunta su Vercel serve un **redeploy** (o un nuovo push) perché una build già esistente non la recepisce da sola.

## Troubleshooting

### "Railpack could not determine how to build the app" / "Script start.sh not found"

Sintomo: il log di build mostra `prepare railpack-...` e un albero file che è la **root del repo** (`backend/`, `docs/`, `frontend/`, ...) invece del contenuto di `backend/`.

Osservato su questo progetto sia su un servizio creato da **Template Railway** (riconoscibile da "Upstream Repo" / "Eject" / "Check for updates" in Settings → Source) sia su un servizio creato collegando **direttamente** il repo GitHub con Root Directory=`backend` già impostato — in entrambi i casi Railpack ha continuato a scansionare la root dell'intero repo. Root Directory da solo non è quindi sufficiente in modo affidabile per questo progetto.

Fix: passare al builder **Dockerfile** (vedi sezione sopra), che bypassa del tutto la fase di rilevamento automatico di Railpack. Se il problema persiste anche con Dockerfile, il prossimo sospetto è il campo **Dockerfile Path** nelle impostazioni del servizio: va verificato se è relativo alla root del repo o a Root Directory (i due comportamenti richiedono un valore diverso, vedi sopra).

### Il servizio era da Template e non risponde ai cambi di Root Directory/Builder

Se cambiare Root Directory e Builder sullo stesso servizio non ha effetto sui log di build, creare un **servizio nuovo** con **"+ New" → "GitHub Repo"** (non da Template) selezionando `MrGhettone/gym-bross` direttamente, ripetere la configurazione (Root Directory, Builder Dockerfile, variabili d'ambiente — si può riusare lo stesso plugin MySQL del progetto), fare il deploy sul nuovo servizio ed eliminare quello vecchio una volta verificato che funziona.

### Healthcheck `/up` fallisce (o una vista Blade qualsiasi restituisce 500) solo su Railway, non in locale

Sintomo: `php artisan serve` in locale funziona, ma su Railway l'healthcheck su `/up` fallisce (o, più in generale, qualunque pagina che renderizza una vista Blade dà errore in produzione ma non in locale).

Causa: `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views` e `storage/logs` sono cartelle vuote — git non traccia cartelle vuote, quindi non esistono nel repo, e sono **anche** esplicitamente escluse da `backend/.dockerignore`. Il risultato è che nell'immagine Docker queste cartelle non esistono affatto. Blade **deve** scrivere la cache dei template compilati in `storage/framework/views/`: senza quella cartella qualunque `view()`/rendering Blade fallisce. In locale non si nota perché quelle cartelle esistono già su disco (create da Laravel durante lo sviluppo).

Fix: `backend/Dockerfile` ricrea queste cartelle esplicitamente dopo `COPY . .` (`RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs && chmod -R 775 storage bootstrap/cache`). Per questo motivo, inoltre, la route `/up` in `routes/web.php` è una route custom che risponde con `response()->json(...)` invece di usare la route di health-check nativa di Laravel (che renderizza una vista Blade): bypassa il problema a monte anche se in futuro la creazione delle cartelle dovesse rompersi di nuovo, con una singola route che non dipende da Blade.

### Le notifiche Web Push non arrivano, in log un errore su GMP/BCMath

Sintomo (visibile solo dopo aver impostato `LOG_CHANNEL=stderr`, vedi sopra): `Log::error` mostra un messaggio tipo *"It is highly recommended to install the GMP or BCMath extension..."*.

Causa: è un **avviso informativo** di `web-token/jwt-library` (dipendenza di `minishlink/web-push`, usata per firmare i JWT VAPID con ECDSA) — di per sé non bloccante, la libreria userebbe comunque un'implementazione più lenta in puro PHP. Ma il gestore errori di Laravel converte automaticamente ogni warning/notice PHP in un'eccezione (`ErrorException`), quindi questo "consiglio" interrompe l'invio della notifica come se fosse un errore vero. `backend/Dockerfile` non installava né `gmp` né `bcmath`.

Fix: aggiunta `bcmath` a `docker-php-ext-install` nel Dockerfile (nessuna libreria di sistema esterna richiesta, a differenza di `gmp` che avrebbe bisogno di `libgmp-dev`). In locale (XAMPP) `bcmath` è già presente di default — per questo il problema non si è mai visto sviluppando in locale, solo dopo il primo deploy reale con un invio push effettivo.

### (Storico) `composer install` falliva con "requires php >=8.4.1"

Fino al 2026-08-20 il progetto era su Laravel 13 (richiede PHP ^8.3), ma il `composer.lock` — generato in locale con `--ignore-platform-reqs` perché la macchina di sviluppo aveva PHP 8.2 — aveva bloccato versioni Symfony (8.1.x) che richiedevano **PHP ≥8.4.1**, più stretto di quanto dichiarato in `composer.json`. Il 2026-08-22 il progetto è stato riportato a **Laravel 12** (richiede solo PHP ^8.2, coerente col PHP 8.2.12 reale della macchina di sviluppo), eliminando il problema alla radice invece di rincorrere versioni PHP più recenti — vedi decisione in [AGENTS.md](../AGENTS.md#decisioni-architetturali--problemi-aperti). Il `composer.lock` attuale è stato rigenerato con `composer update` **senza** `--ignore-platform-reqs`, direttamente su PHP 8.2.12, quindi verificato installabile per davvero.

## Limiti noti del piano free

- Filesystem effimero: non fare affidamento su file scritti su disco tra un deploy e l'altro (per questo si usa `SESSION_DRIVER=database` invece di `file`, e `CACHE_STORE=database` invece di `file`).
- Il servizio può andare in sleep/riavviarsi per inattività a seconda dei limiti del piano: la prima richiesta dopo un periodo di inattività può essere più lenta.
- `php artisan serve` è il server di sviluppo integrato di PHP: sufficiente per un MVP a basso traffico sul piano free, ma non è pensato per carichi di produzione elevati. Da rivalutare (es. PHP-FPM + Nginx via Dockerfile) se il traffico cresce.

## Frontend

Deployato su **Vercel** (dominio separato dal backend Railway — per questo l'autenticazione è a token Bearer e non cookie-based, vedi [docs/authentication.md](authentication.md)). Variabili d'ambiente da impostare su Vercel (Project → Settings → Environment Variables), entrambe richieste in build, non a runtime:

```
VITE_API_URL=https://<dominio-pubblico-backend-railway>/api/v1
VITE_VAPID_PUBLIC_KEY=<stesso valore di VAPID_PUBLIC_KEY sul backend>
```

Ricordarsi il **redeploy** su Vercel dopo aver aggiunto/cambiato una variabile `VITE_*`.
