FROM php:8.1-fpm AS php

WORKDIR /var/www

ARG UNAME=developer
ARG UID=1000
ARG GID=1000

RUN groupadd -g $GID -o $UNAME
RUN useradd -m -u $UID -g $GID -o -s /bin/bash $UNAME

COPY . /var/www
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

RUN apt-get update && apt-get install -y \
    gnupg curl zlib1g-dev libzip-dev libpq-dev libxml2-dev libicu-dev g++ git unzip jq libsqlite3-dev sqlite3 \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install opcache \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install pdo_sqlite

RUN pecl install apcu
RUN docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer && php -r "unlink('composer-setup.php');" || php -r "unlink('composer-setup.php');"

RUN set -xe \
  && composer install --no-scripts --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R developer:developer /var/www/vendor

USER $UNAME

EXPOSE 9000