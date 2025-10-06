# --- Base image ---
FROM dunglas/frankenphp

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# --- Install PHP extensions ---
RUN install-php-extensions \
    pcntl \
    posix \
    pdo \
    pdo_mysql \
    redis \
    bcmath \
    sockets

# --- Set working directory ---
WORKDIR /app

# Composer global installation (optional if image already has it)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- Copy only composer files first (for caching) ---
COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader

# --- Copy rest of project ---
COPY . .

RUN composer dump-autoload

# --- Permissions for development ---
RUN chmod -R 777 storage bootstrap/cache

# --- Default command for dev ---
ENTRYPOINT ["php", "artisan", "octane:start", "--server=frankenphp", "--watch", "--max-requests=1", "--host=0.0.0.0", "--port=8000"]
