release: chmod -R 755 storage bootstrap/cache
start: php artisan serve --host=0.0.0.0 --port=$PORT
web: rm -rf bootstrap/cache/*.php && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear && chmod -R 755 storage bootstrap/cache && php artisan serve --host=0.0.0.0 --port=8080
