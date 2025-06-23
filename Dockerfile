FROM php:8.2-fpm

# Instala dependências do sistema necessárias para GD e outras extensões
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    netcat-openbsd

# Configura e instala a extensão GD com suporte a JPEG e FreeType, e outras extensões PHP
RUN docker-php-ext-configure gd \
        --with-jpeg \
        --with-freetype \
    && docker-php-ext-install \
        gd \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia os arquivos do projeto
COPY . .

# Instala dependências do Laravel
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --optimize-autoloader --no-dev

# Remove cache antigo de config
RUN rm -f bootstrap/cache/config.php

# Copia o script de entrada e dá permissão
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expõe a porta usada pelo Render
EXPOSE 10000

# Define o ponto de entrada
ENTRYPOINT ["/entrypoint.sh"]
