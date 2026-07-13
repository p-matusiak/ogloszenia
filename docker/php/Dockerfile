FROM php:8.4-fpm-alpine

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        redis \
        gd \
        intl \
        zip \
        bcmath \
        pcntl \
        opcache \
    && rm -f /usr/local/bin/install-php-extensions

# uid 1000 matches the host developer account, so files written from inside the
# container stay editable on the host bind mount.
RUN deluser www-data 2>/dev/null || true \
    && addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

WORKDIR /var/www/html

USER app

CMD ["php-fpm"]
