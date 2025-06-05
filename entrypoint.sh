#!/bin/bash

# Aguarda conexão com o banco
echo "Esperando o PostgreSQL ficar disponível em $DB_HOST:5432..."
until nc -z "$DB_HOST" 5432; do
  echo "Ainda sem conexão... tentando novamente..."
  sleep 2
done

echo "PostgreSQL está acessível. Continuando o deploy..."

# Comandos Laravel
php artisan config:clear
php artisan config:cache
php artisan migrate --force || true

# Sobe o servidor
php-fpm
