FROM dunglas/frankenphp:php8.2-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add supervisor

ENV SERVER_NAME=:80

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    sockets

COPY . /app

COPY docker/app/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN cp "/app/docker/app/php.ini" "$PHP_INI_DIR/php.ini"
RUN cp /app/docker/app/root /etc/crontabs/root

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN composer install --no-dev

WORKDIR /app

CMD ["supervisord", "-c" , "/etc/supervisor/conf.d/supervisord.conf"]