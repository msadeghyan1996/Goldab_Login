FROM php:8.2-fpm-bullseye

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) intl pdo_pgsql bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# PHP-FPM listen override so nginx can reach it across containers
COPY docker/php-fpm/zz-override.conf /usr/local/etc/php-fpm.d/zz-override.conf

# Entrypoint to install deps, wait for DB, migrate, cache, etc.
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]


