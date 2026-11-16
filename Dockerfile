# Imagen para desplegar ReservaPistes en Render.
# Etapa 1: compila el CSS y el JS con Vite.
# Etapa 2: PHP 8.2 + Apache con las extensiones que necesita Laravel con PostgreSQL.

# --- Etapa 1: assets ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci
COPY resources ./resources
RUN npm run build

# --- Etapa 2: aplicacion ---
FROM php:8.2-apache

# Extensiones de PHP: pdo_pgsql para PostgreSQL, zip e intl para Laravel.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql zip intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Las dependencias primero, para aprovechar la cache de capas de Docker.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# El codigo del proyecto y los assets ya compilados.
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

# Apache sirve desde public/ (donde esta index.php).
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

CMD ["entrypoint"]
