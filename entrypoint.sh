#!/bin/sh

# Extrai host e porta da DATABASE_URL com fallback
echo "DATABASE_URL: $DATABASE_URL"

DB_HOST=$(echo "$DATABASE_URL" | sed -nE 's|.*@([^:/]+):[0-9]+/.*|\1|p')
DB_PORT=$(echo "$DATABASE_URL" | sed -nE 's|.*:([0-9]+)/.*|\1|p')

if [ -z "$DB_HOST" ] || [ -z "$DB_PORT" ]; then
  echo "ERRO: nao foi possivel extrair DB_HOST ou DB_PORT da DATABASE_URL."
  echo "Valor atual da variavel: $DATABASE_URL"
  exit 1
fi

echo "Aguardando PostgreSQL em $DB_HOST:$DB_PORT..."

for i in $(seq 1 60); do
  nc -z "$DB_HOST" "$DB_PORT" && break
  echo "Tentativa $i: ainda sem conexao... aguardando..."
  sleep 2
done

echo "Banco disponivel. Iniciando Laravel..."
php artisan config:cache
php artisan migrate --force
php-fpm

