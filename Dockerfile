# Imagen de producción para Render. El panel y el portal corren en un solo contenedor.
FROM php:8.4-apache

# RNF06: PHP2Shell (CVE-2026-109618 y 109619) afecta a las versiones 8.4 anteriores a 8.4.27.
RUN php -r "exit(version_compare(PHP_VERSION, '8.4.27', '>=') ? 0 : 1);"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libpq-dev libwebp-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" exif gd intl opcache pcntl pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-fixa.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers deflate \
    && sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

ENV PORT=10000 \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_TIMEZONE=America/La_Paz \
    APP_LOCALE=es \
    APP_FALLBACK_LOCALE=es \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=file \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=mailgun

WORKDIR /var/www/html
COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN mkdir -p bootstrap/cache storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && composer audit --no-dev \
    && rm /usr/local/bin/composer

# Lo que no depende de secretos se deja listo en la imagen. config:cache y route:cache corren
# al arrancar: las rutas de Livewire incluyen un hash del APP_KEY.
RUN php artisan filament:assets \
    && php artisan icons:cache \
    && php artisan filament:cache-components \
    && php artisan view:cache \
    && php artisan event:cache \
    && rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php

RUN chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache /var/run/apache2 /var/log/apache2

USER www-data

EXPOSE 10000

CMD ["docker/start.sh"]
