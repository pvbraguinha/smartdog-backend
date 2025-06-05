#!/bin/sh

# Aguarda conexão com o banco via script separado
sh /wait-for-db.sh

echo "🎯 Configurando Laravel..."

php artisan config:clear
php artisan config:cache
php artisan migrate --force || true

echo "🚀 Iniciando servidor PHP-FPM..."
php-fpm
