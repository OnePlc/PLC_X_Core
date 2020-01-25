FROM php:7.3-apache

RUN apt-get update \
 && apt-get install -y git zlib1g-dev libicu-dev libzip-dev\
 && docker-php-ext-install zip \
 && docker-php-ext-install pdo pdo_mysql \
 && docker-php-ext-install intl \
 && a2enmod rewrite \
 && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
 && mv /var/www/html /var/www/public \
 && curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

COPY /var/www/config/autoload/global.php.dist /var/www/config/autoload/global.php
COPY /var/www/config/autoload/local.php.dist /var/www/config/autoload/local.php

WORKDIR /var/www
