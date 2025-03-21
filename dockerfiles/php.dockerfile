FROM php:8.2-fpm

COPY ./app /var/www/html

WORKDIR /var/www/html

RUN docker-php-ext-install pdo pdo_mysql