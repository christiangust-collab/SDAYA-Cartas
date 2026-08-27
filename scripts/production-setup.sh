#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(dirname "$SCRIPT_DIR")
ENV_FILE="$PROJECT_DIR/.env.production"
ENV_EXAMPLE="$PROJECT_DIR/.env.production.example"

cd "$PROJECT_DIR"

if ! docker compose version >/dev/null 2>&1; then
    echo "ERROR: Docker Compose no está disponible." >&2
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    echo "Se creó .env.production. Edítalo y vuelve a ejecutar este comando."
    exit 2
fi

if grep -Eq 'ejemplo\.com|CAMBIAR_POR_' "$ENV_FILE"; then
    echo "ERROR: .env.production todavía contiene dominio o contraseñas de ejemplo." >&2
    echo "Configura APP_URL, APP_DOMAIN, DB_PASSWORD, ADMIN_EMAIL y ADMIN_PASSWORD." >&2
    exit 2
fi

if grep -q '^APP_KEY=$' "$ENV_FILE"; then
    if command -v openssl >/dev/null 2>&1; then
        APP_KEY_VALUE="base64:$(openssl rand -base64 32 | tr -d '\n')"
    else
        APP_KEY_VALUE="base64:$(docker run --rm php:8.3-cli php -r 'echo base64_encode(random_bytes(32));')"
    fi

    sed "s|^APP_KEY=$|APP_KEY=$APP_KEY_VALUE|" "$ENV_FILE" > "$ENV_FILE.tmp"
    mv "$ENV_FILE.tmp" "$ENV_FILE"
    chmod 600 "$ENV_FILE"
    echo "Se generó APP_KEY y se protegió el archivo de entorno."
fi

docker compose --env-file "$ENV_FILE" -f compose.production.yaml up -d --build
docker compose --env-file "$ENV_FILE" -f compose.production.yaml exec -T app php artisan migrate --seed --force
docker compose --env-file "$ENV_FILE" -f compose.production.yaml exec -T app php artisan optimize

echo "Despliegue finalizado. Verifica https://$(sed -n 's/^APP_DOMAIN=//p' "$ENV_FILE" | tail -n 1 | tr -d '"\r')"
