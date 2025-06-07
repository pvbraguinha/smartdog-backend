#!/bin/sh

# Usar variáveis de ambiente individuais se DATABASE_URL não estiver disponível
if [ -z "$DATABASE_URL" ] || echo "$DATABASE_URL" | grep -q "smartdog-db"; then
  echo "Usando variáveis de ambiente individuais para conexão com o banco de dados"
  # Use as informações que você compartilhou anteriormente
  DB_HOST="dpg-d10v3rm3jp1c739d1ae0-a.internal"
  DB_PORT="5432"
else
  # Extrai host e porta da DATABASE_URL com fallback
  echo "DATABASE_URL: $DATABASE_URL"
  DB_HOST=$(echo "$DATABASE_URL" | sed -nE 's|.*@([^:/]+):[0-9]+/.*|\1|p')
  DB_PORT=$(echo "$DATABASE_URL" | sed -nE 's|.*:([0-9]+)/.*|\1|p')
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
php-fpm -F