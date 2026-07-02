FROM php:8.3-fpm-alpine

RUN apk add --no-cache supervisor postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl

RUN mkdir -p /etc/supervisor/logs

COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
