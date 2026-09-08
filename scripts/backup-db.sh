#!/usr/bin/env bash
#
# Backup diario de la base de datos de producción. Pensado para lanzarse por
# cron desde la raíz del proyecto en el servidor, ej.:
#   0 3 * * * cd /ruta/al/proyecto && ./scripts/backup-db.sh >> storage/logs/backup.log 2>&1
#
# Guarda el .sql.gz en $BACKUP_DIR y borra los que tengan más de $KEEP_DAYS
# días. Esto es solo la copia local — cópiala también fuera del propio
# servidor (S3, Backblaze, rsync a otra máquina...) según dónde se despliegue
# finalmente; el paso concreto de subida se añade cuando se decida el hosting.

set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"
COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"

mkdir -p "$BACKUP_DIR"

DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)"
DB_DATABASE="$(grep -m1 '^DB_DATABASE=' .env | cut -d= -f2-)"
DB_USERNAME="$(grep -m1 '^DB_USERNAME=' .env | cut -d= -f2-)"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
DEST="$BACKUP_DIR/${DB_DATABASE}_${TIMESTAMP}.sql.gz"

docker compose -f "$COMPOSE_FILE" exec -T mysql \
    mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$DEST"

echo "Backup guardado en $DEST"

find "$BACKUP_DIR" -name '*.sql.gz' -type f -mtime "+${KEEP_DAYS}" -delete
