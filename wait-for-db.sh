#!/bin/sh

echo "⏳ Aguardando DNS do PostgreSQL resolver em $DB_HOST:$DB_PORT..."

# Aguarda DNS resolver o host
until getent hosts "$DB_HOST" > /dev/null 2>&1; do
  echo "⏳ Aguardando resolução DNS de $DB_HOST..."
  sleep 1
done

# Depois tenta abrir a conexão TCP
echo "🔁 Testando conexão TCP com $DB_HOST:$DB_PORT..."
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  echo "⛔ Ainda sem conexão TCP... tentando novamente..."
  sleep 1
done

echo "✅ PostgreSQL está pronto e acessível. Continuando..."
