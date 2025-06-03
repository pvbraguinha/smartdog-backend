FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Apaga cache de config do Laravel antes de subir
RUN rm -rf bootstrap/cache/config.php

RUN composer install --optimize-autoloader --no-dev

# Expondo a porta
EXPOSE 8080

CMD php artisan config:clear && php artisan cache:clear && php artisan serve --host=0.0.0.0 --port=8080


