#!/bin/sh

# Usar endereço externo do banco de dados
echo "Usando endereço externo do banco de dados"
DB_HOST="dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com"
DB_PORT="5432"

echo "Aguardando PostgreSQL em $DB_HOST:$DB_PORT..."

for i in $(seq 1 60); do
  nc -z "$DB_HOST" "$DB_PORT" && break
  echo "Tentativa $i: ainda sem conexao... aguardando..."
  sleep 2
done

echo "Banco disponivel. Iniciando Laravel..."
php artisan config:cache
php artisan migrate --force
php-fpm -F
