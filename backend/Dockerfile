FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# storage/framework/{cache/data,sessions,views} e storage/logs sono esclusi
# da .dockerignore (git non traccia cartelle vuote, non c'e' nulla da
# escludere/includere): senza questo passaggio non esistono nell'immagine,
# e qualunque rendering di vista Blade fallisce provando a scrivere la
# cache compilata in storage/framework/views (es. la route di health-check
# nativa di Laravel, o le pagine di errore di produzione).
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chmod -R 775 storage bootstrap/cache

RUN composer dump-autoload --optimize --no-dev \
    && chmod +x start.sh

CMD ["bash", "start.sh"]
