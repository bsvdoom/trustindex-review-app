FROM php:8.3-apache

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/server-name.conf /etc/apache2/conf-available/server-name.conf

RUN a2enconf server-name

WORKDIR /var/www/html
