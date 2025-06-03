FROM php:8.2-fpm

# Instala dependências do sistema, incluindo suporte ao PostgreSQL
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Remove cache de configuração do Laravel (caso exista)
RUN rm -rf bootstrap/cache/config.php

# Instala dependências do Laravel em modo produção
RUN composer install --optimize-autoloader --no-dev

# Expõe a porta padrão
EXPOSE 8080

# Define o comando de inicialização
CMD php artisan config:clear && php artisan cache:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
