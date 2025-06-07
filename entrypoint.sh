#!/bin/sh

# Criar arquivo .env com as configurações corretas
echo "Criando arquivo .env com as configurações corretas..."
cat > .env << EOF
APP_NAME=Smartdog
APP_ENV=production
APP_KEY=base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=
APP_DEBUG=true
APP_URL=https://smartdog-backend.onrender.com

DB_CONNECTION=pgsql
DB_HOST=dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com
DB_PORT=5432
DB_DATABASE=smartdog_db_fnp8
DB_USERNAME=smartdog_db_fnp8_user
DB_PASSWORD=0SMTQjMgkWVSii6sUumnTXNfBp8qweKd
EOF

# Exporta APP_KEY para evitar falha por cache incompleto
export APP_KEY=base64:CdANHmCLLwnCYV7btlo6V/2qjNJ2ckiwh0fvLrkxjIQ=

echo "Aguardando PostgreSQL em dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com:5432..."

for i in $(seq 1 60 ); do
  nc -z dpg-d10v3rm3jp1c739d1ae0-a.frankfurt-postgres.render.com 5432 && break
  echo "Tentativa $i: ainda sem conexao... aguardando..."
  sleep 2
done

echo "Banco disponível. Iniciando Laravel..."

# Limpar todos os caches e garantir que nada está persistido
rm -rf bootstrap/cache/*

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Executar migrações
php artisan migrate --force

# Iniciar servidor embutido Laravel
echo "Iniciando servidor embutido Laravel..."
php -S 0.0.0.0:10000 -t public
