FROM php:8.2-cli

# Instala dependências
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

# Remove cache antigo
RUN rm -rf bootstrap/cache/config.php

# Exponha a porta usada pelo servidor embutido
EXPOSE 8080

# Usa o servidor embutido do PHP, ideal para Railway
CMD php artisan config:clear && php artisan cache:clear && php -S 0.0.0.0:${PORT:-8080} -t public


