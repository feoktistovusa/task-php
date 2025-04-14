FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev

RUN docker-php-ext-install zip pdo_mysql

RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/error-reporting.ini

RUN a2enmod rewrite

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

COPY . .

RUN chown -R www-data:www-data /var/www/html
