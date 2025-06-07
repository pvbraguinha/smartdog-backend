#!/bin/sh

# Usar endereço externo do banco de dados
echo "Usando endereço externo do banco de dados"

# Configurar variáveis de ambiente diretamente
export DB_HOST="dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com"
export DB_PORT="5432"
export DB_CONNECTION="pgsql"
export DB_DATABASE="smartdog_db_fnp8"
export DB_USERNAME="smartdog_db_fnp8_user"
export DB_PASSWORD="0SMTQjMgkWVSii6sUumnTXNfBp8qweKd"

echo "Aguardando PostgreSQL em $DB_HOST:$DB_PORT..."

for i in $(seq 1 60); do
  nc -z "$DB_HOST" "$DB_PORT" && break
  echo "Tentativa $i: ainda sem conexao... aguardando..."
  sleep 2
done

echo "Banco disponivel. Iniciando Laravel..."

# Limpar cache de configuração e recriar
php artisan config:clear
php artisan config:cache

# Executar migrações
php artisan migrate --force

# Iniciar servidor PHP-FPM ou servidor embutido
if command -v php-fpm > /dev/null 2>&1; then
  php-fpm -F
else
  echo "PHP-FPM não encontrado, usando servidor embutido..."
  php -S 0.0.0.0:10000 -t public
fi
