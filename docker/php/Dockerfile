FROM php:8.4-fpm-alpine

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

# PECL недоступен из части production-сетей, поэтому собираем закреплённую
# версию phpredis из исходников GitHub. PHPIZE_DEPS уже установлен выше.
ARG PHPREDIS_VERSION=6.1.0
RUN git clone --depth 1 --branch "${PHPREDIS_VERSION}" \
      https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis

# Xdebug намеренно не включается в production-образ.

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
