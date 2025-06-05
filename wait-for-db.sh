#!/bin/sh

echo "⏳ Aguardando PostgreSQL em $DB_HOST:$DB_PORT..."

while ! nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done

echo "✅ PostgreSQL está pronto. Iniciando aplicação..."