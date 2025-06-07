#!/bin/sh

# Usar endereço externo do banco de dados
echo "Usando endereço externo do banco de dados"

# Configurar variáveis de ambiente diretamente no arquivo .env
echo "Atualizando arquivo .env com as configurações corretas..."
sed -i "s|DB_HOST=.*|DB_HOST=dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com|g" .env
sed -i "s|DB_PORT=.*|DB_PORT=5432|g" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=smartdog_db_fnp8|g" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=smartdog_db_fnp8_user|g" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=0SMTQjMgkWVSii6sUumnTXNfBp8qweKd|g" .env

echo "Aguardando PostgreSQL em dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com:5432..."

for i in $(seq 1 60); do
  nc -z dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com 5432 && break
  echo "Tentativa $i: ainda sem conexao... aguardando..."
  sleep 2
done

echo "Banco disponivel. Iniciando Laravel..."

# Limpar cache de configuração e recriar
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
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