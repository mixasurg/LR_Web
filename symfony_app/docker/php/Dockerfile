FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    sqlite-dev \
    && docker-php-ext-install \
    intl \
    pdo_sqlite \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-interaction --prefer-dist \
    && composer clear-cache

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm", "-F"]
