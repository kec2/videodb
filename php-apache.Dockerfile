FROM php:8.2-apache

# install GD dependencies
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        nano \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install php xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# install mysql extention
RUN docker-php-ext-install mysqli

# copy php config to installation.
COPY php-apache/php-dev.ini /usr/local/etc/php/conf.d/
COPY ./ /var/www/html/

# install Composer 2
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# install packages needed by composer
RUN apt-get install -y git unzip

# create folder for composer with all rights.
RUN mkdir /.composer \
    && mkdir -p /.cache/psalm \
    && chmod -R 777 /.composer \
    && chmod -R 777 /.cache/psalm

RUN composer install