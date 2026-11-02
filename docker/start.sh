#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "Falta APP_KEY: agrégala en las variables de entorno de Render." >&2
    exit 1
fi

# Las migraciones no corren aquí: un reinicio nunca debe cambiar la base de datos.
php artisan config:cache
php artisan route:cache

exec apache2-foreground
