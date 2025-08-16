FROM php:8.2-fpm-alpine

RUN apk add --no-cache autoconf g++ make git unzip \
 && pecl install mongodb \
 && docker-php-ext-enable mongodb \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /var/www/html
