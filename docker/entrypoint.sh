#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/private/documentos \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Soporte dinámico para puerto en entornos cloud (Render, Heroku, etc.)
HTTP_PORT="${PORT:-80}"
if [ "${HTTP_PORT}" != "80" ] && [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/Listen 80/Listen ${HTTP_PORT}/g" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${HTTP_PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY no está definida. Ejecuta scripts/docker-setup.sh o configura el archivo de entorno." >&2
    exit 1
fi

# Limpieza de cachés de compilación para cargar variables de entorno activas
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

# Ejecución de migraciones y catálogos controlada por la variable RUN_MIGRATIONS (false por defecto)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "RUN_MIGRATIONS=true: ejecutando migraciones de base de datos..."
    php artisan migrate --force --no-interaction || echo "AVISO: No se pudieron ejecutar las migraciones de forma automática." >&2

    # Poblar catálogos iniciales de áreas y tipos de documento si están vacíos
    php artisan db:seed --class=AreaSeeder --force --no-interaction >/dev/null 2>&1 || true
    php artisan db:seed --class=TipoSeeder --force --no-interaction >/dev/null 2>&1 || true
fi

# Sincronización segura e idempotente del usuario administrador inicial
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    echo "Sincronizando usuario administrador inicial..."
    php artisan db:seed --class=UsuarioAdministradorSeeder --force --no-interaction || echo "AVISO: No se pudo configurar el usuario administrador inicial." >&2
fi

exec "$@"

