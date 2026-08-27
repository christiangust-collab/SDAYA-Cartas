#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(dirname "$SCRIPT_DIR")
ENV_FILE="$PROJECT_DIR/.env.docker"
ENV_EXAMPLE="$PROJECT_DIR/.env.docker.example"

cd "$PROJECT_DIR"

if ! docker compose version >/dev/null 2>&1; then
    echo "ERROR: Docker Compose no está disponible. Instala Docker Desktop o Docker Engine con Compose." >&2
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    echo "Se creó .env.docker desde el ejemplo seguro."
fi

if grep -q '^APP_KEY=$' "$ENV_FILE"; then
    if command -v openssl >/dev/null 2>&1; then
        APP_KEY_VALUE="base64:$(openssl rand -base64 32 | tr -d '\n')"
    else
        APP_KEY_VALUE="base64:$(docker run --rm php:8.3-cli php -r 'echo base64_encode(random_bytes(32));')"
    fi

    sed "s|^APP_KEY=$|APP_KEY=$APP_KEY_VALUE|" "$ENV_FILE" > "$ENV_FILE.tmp"
    mv "$ENV_FILE.tmp" "$ENV_FILE"
    echo "Se generó APP_KEY."
fi

docker compose --env-file "$ENV_FILE" -f compose.yaml up -d --build
docker compose --env-file "$ENV_FILE" -f compose.yaml exec -T app php artisan migrate --seed --force
docker compose --env-file "$ENV_FILE" -f compose.yaml exec -T app php artisan optimize:clear

APP_PORT=$(sed -n 's/^APP_PORT=//p' "$ENV_FILE" | tail -n 1 | tr -d '"\r')
APP_PORT=${APP_PORT:-8080}

echo
echo "SDAYA Cartas quedó instalado."
echo "Dirección: http://localhost:$APP_PORT"
echo "Usuario inicial: revisa ADMIN_EMAIL en .env.docker"
echo "Contraseña inicial: revisa ADMIN_PASSWORD en .env.docker y cámbiala después del primer ingreso."
