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

# Garante permissões
RUN chmod -R 755 storage bootstrap/cache

# Instala dependências do Laravel
RUN composer install --no-scripts --no-plugins --optimize-autoloader --ignore-platform-reqs

# Expõe a porta padrão
EXPOSE 8080

# Usa servidor PHP embutido em vez do artisan serve
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]

