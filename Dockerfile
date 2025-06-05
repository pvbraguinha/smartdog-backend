FROM php:8.2-cli

# Instala dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libpq-dev netcat-openbsd \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --optimize-autoloader --no-dev

# Remove cache antigo
RUN rm -rf bootstrap/cache/config.php

# Copia os scripts de entrada e dá permissão
COPY wait-for-db.sh /wait-for-db.sh
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /wait-for-db.sh /entrypoint.sh

# Expõe a porta usada pelo servidor PHP embutido (requerida pela Render)
EXPOSE 10000

# Usa o script de entrada como ponto de entrada
ENTRYPOINT ["/entrypoint.sh"]

