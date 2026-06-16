FROM php:8.1-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    shadow

# PHP extensions
RUN docker-php-ext-configure intl && \
    docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        exif \
        pcntl \
        bcmath \
        mbstring \
        intl \
        opcache

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./php.ini /usr/local/etc/php/conf.d/codeguardian.ini

WORKDIR /var/www/html

# Run as non-root www-data
RUN usermod -u 1000 www-data 2>/dev/null || true
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
