FROM php:8.3-fpm-alpine

RUN apk update && apk upgrade

RUN apk add --no-cache \
    curl \
    bash \
    nano \
    mc \
    git \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
      pdo \
      pdo_pgsql \
      pgsql \
      bcmath \
      intl \
      opcache \
      pcntl \
      zip \
      gd \
      exif \
      sockets

RUN pecl install redis \
    && docker-php-ext-enable redis

# Xdebug (dev). Отключить в production-сборке при необходимости.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Удаляем только -dev пакеты; runtime-библиотеки для .so оставляем явно
RUN apk del --no-cache \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      postgresql-dev \
      icu-dev \
      libxml2-dev \
      libzip-dev \
      linux-headers \
    && apk add --no-cache \
      freetype \
      libjpeg-turbo \
      libpng \
      libpq \
      icu-libs \
      libzip \
      libxml2 \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /var/www
