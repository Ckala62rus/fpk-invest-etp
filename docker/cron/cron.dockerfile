FROM php:8.3-cli-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY docker/cron/crontab /etc/crontabs/root

CMD ["crond", "-f", "-l", "2"]
