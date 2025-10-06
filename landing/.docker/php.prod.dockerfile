# ------------------------------------------------------------
# STAGE 1 — PHP dependencies
# ------------------------------------------------------------
FROM php:8.3-cli AS php-builder

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev libonig-dev libxml2-dev libsqlite3-dev \
    && docker-php-ext-install \
        pdo pdo_mysql pdo_sqlite bcmath sockets pcntl posix \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Composer (copy from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload


# ------------------------------------------------------------
# STAGE 2 — Node.js for building assets
# ------------------------------------------------------------
FROM node:20 AS node-builder

WORKDIR /app
COPY package*.json ./

RUN npm install

COPY --from=php-builder /app /app

RUN npm run build || echo "Vite build skipped (no scripts defined)"


# ------------------------------------------------------------
# STAGE 3 — Final FrankenPHP runtime (for local dev)
# ------------------------------------------------------------
FROM dunglas/frankenphp:latest

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libonig-dev libxml2-dev libsqlite3-dev \
    && install-php-extensions \
        pdo_mysql pdo_sqlite bcmath sockets pcntl posix redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=php-builder /app /app
COPY --from=node-builder /app/public /app/public

RUN chmod -R 777 storage bootstrap/cache

RUN php artisan optimize:clear || true

# port
EXPOSE 8000

# ------------------------------------------------------------
# Default command (Octane + watch)
# ------------------------------------------------------------
ENTRYPOINT ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]
