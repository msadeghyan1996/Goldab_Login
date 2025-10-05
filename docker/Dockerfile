FROM php:8.2-apache

ENV TZ="Asia/Tehran"

# Copy virtual host into container
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable rewrite mode
RUN a2enmod rewrite

USER root
# Install necessary packages

RUN apt-get -o Acquire::Check-Valid-Until=false update \
   	&& apt-get install  \
                    libicu-dev \
                    libzip-dev \
                    wget \
                    nano \
                    zip\
                    unzip \
                     -y --no-install-recommends

# Install PHP Extensions
RUN docker-php-ext-install zip pdo_mysql pdo
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

#RUN pecl install -o -f xdebug \
#    && docker-php-ext-enable xdebug

# Copy composer installable
#COPY ./install-composer.sh ./
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy php.ini

# Cleanup packages and install composer
RUN apt-get purge -y g++ \
    && apt-get autoremove -y \
    && rm -r /var/lib/apt/lists/* \
    && rm -rf /tmp/* \


# Change the current working directory
WORKDIR /var/www/html

# Change the owner of the container document root
RUN chown -R www-data:www-data /var/www

# Start Apache in foreground
CMD ["apache2-foreground"]
