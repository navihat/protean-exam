FROM php:8.3-fpm-bookworm

ARG UID=1000
ARG GID=1000

# System libs for PHP extensions. qpdf + ffmpeg are used to cut document previews (T21).
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libssl-dev \
        qpdf ffmpeg \
    && docker-php-ext-install pdo_mysql zip intl bcmath pcntl opcache \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint

# Run as the host user so files created in the bind mount stay editable on the host.
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data \
    && chmod +x /usr/local/bin/entrypoint

USER www-data
WORKDIR /var/www/html

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
