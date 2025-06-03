FROM php:8.2-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia código para o container
COPY . .

# Define APP_KEY diretamente
ENV APP_KEY=base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=

RUN composer install --no-scripts --no-plugins --optimize-autoloader --ignore-platform-reqs

# Expõe porta padrão usada pelo Railway
EXPOSE 8080

CMD bash -c "php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"

