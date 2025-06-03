FROM php:8.2-fpm

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

# Remove cache de configuração antigo
RUN rm -rf bootstrap/cache/config.php

# Exponha a porta padrão do PHP-FPM
EXPOSE 9000

# Roda o PHP-FPM em produção
CMD ["php-fpm"]

