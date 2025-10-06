# --------------------------------------------------------
# Base image
# --------------------------------------------------------
FROM dunglas/frankenphp:latest

# --------------------------------------------------------
# Install dependencies and Supervisor
# --------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------------
# Install required PHP extensions for Horizon & Scheduler
# --------------------------------------------------------
RUN install-php-extensions \
    pcntl \
    posix \
    pdo_mysql \
    redis \
    bcmath

# --------------------------------------------------------
# Set working directory
# --------------------------------------------------------
WORKDIR /app

# --------------------------------------------------------
# Install Composer
# --------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --------------------------------------------------------
# Copy composer files for cache
# --------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

# --------------------------------------------------------
# Copy the rest of the project
# --------------------------------------------------------
COPY . .

RUN composer dump-autoload

# --------------------------------------------------------
# Cache config for faster startup
# --------------------------------------------------------
RUN php artisan optimize:clear && php artisan config:cache || true

# --------------------------------------------------------
# Fix permissions for local dev
# --------------------------------------------------------
RUN chmod -R 777 storage bootstrap/cache

# --------------------------------------------------------
# Setup Supervisor
# --------------------------------------------------------
RUN mkdir -p /etc/supervisor/logs/
COPY ./.docker/supervisor/supervisor.conf /etc/supervisor/supervisord.conf

# --------------------------------------------------------
# Default command (Supervisor will manage both Horizon & Scheduler)
# --------------------------------------------------------
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
