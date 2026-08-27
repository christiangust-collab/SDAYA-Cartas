#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_DIR=$(dirname "$SCRIPT_DIR")
ENV_FILE=${1:-"$PROJECT_DIR/.env.docker"}
COMPOSE_FILE=${2:-"$PROJECT_DIR/compose.yaml"}
STAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$PROJECT_DIR/backups/sdaya_$STAMP"

cd "$PROJECT_DIR"
mkdir -p "$BACKUP_DIR"

DB_CONTAINER=$(docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" ps -q db)
APP_CONTAINER=$(docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" ps -q app)

if [ -z "$DB_CONTAINER" ] || [ -z "$APP_CONTAINER" ]; then
    echo "ERROR: Los servicios app y db deben estar en ejecución." >&2
    exit 1
fi

docker exec "$DB_CONTAINER" sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc -f /tmp/sdaya_database.dump'
docker cp "$DB_CONTAINER:/tmp/sdaya_database.dump" "$BACKUP_DIR/database.dump"
docker exec "$DB_CONTAINER" rm -f /tmp/sdaya_database.dump

docker exec "$APP_CONTAINER" tar -czf /tmp/sdaya_documents.tar.gz -C /var/www/html/storage/app/private .
docker cp "$APP_CONTAINER:/tmp/sdaya_documents.tar.gz" "$BACKUP_DIR/documents.tar.gz"
docker exec "$APP_CONTAINER" rm -f /tmp/sdaya_documents.tar.gz

printf '%s\n' \
    "SDAYA Cartas - respaldo $STAMP" \
    "Base de datos: database.dump (formato personalizado de PostgreSQL)" \
    "Documentos: documents.tar.gz" \
    "Entorno: $ENV_FILE" \
    "Compose: $COMPOSE_FILE" > "$BACKUP_DIR/LEEME.txt"

echo "Respaldo creado en: $BACKUP_DIR"
