#!/usr/bin/env bash
#
# Automatiza el runbook manual ya documentado en docs/operaciones/ci-cd.md:
# respaldo de base de datos, migracion controlada, y reemplazo de
# contenedores por una imagen nueva, con verificacion de salud al final.
#
# Uso (desde la raiz del repo clonado en el VPS, junto a compose.prod.yaml
# y un .env ya completado a partir de .env.production.example):
#
#   ./scripts/deploy.sh <sha-corto-de-la-imagen>
#
# El tag SIEMPRE se pide explicito (nunca "latest") para que cada
# despliegue sea reproducible y quede claro que version quedo corriendo.
# Ver docs/operaciones/despliegue-vps.md para el procedimiento completo.

set -euo pipefail

COMPOSE_FILE="compose.prod.yaml"
IMAGE_TAG="${1:-}"

if [ -z "$IMAGE_TAG" ]; then
    echo "Uso: $0 <sha-corto-de-la-imagen>" >&2
    echo "Ejemplo: $0 a1b2c3d" >&2
    exit 1
fi

if [ ! -f ".env" ]; then
    echo "No existe .env en este directorio. Copialo desde .env.production.example y completalo primero." >&2
    exit 1
fi

echo "==> Desplegando ghcr.io/edudrive-official/edudrive-api:${IMAGE_TAG}"

# Deja registrado el tag desplegado para que docker compose lo resuelva
# via \${IMAGE_TAG} en compose.prod.yaml.
if grep -q '^IMAGE_TAG=' .env; then
    sed -i.bak "s/^IMAGE_TAG=.*/IMAGE_TAG=${IMAGE_TAG}/" .env && rm -f .env.bak
else
    echo "IMAGE_TAG=${IMAGE_TAG}" >> .env
fi

APP_WAS_RUNNING=false
if [ -n "$(docker compose -f "$COMPOSE_FILE" ps --services --status running | grep -x app || true)" ]; then
    APP_WAS_RUNNING=true
fi

if [ "$APP_WAS_RUNNING" = true ]; then
    echo "==> Respaldando la base de datos antes de migrar"
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan backup:database
else
    echo "==> Primer despliegue detectado (no hay contenedor 'app' corriendo todavia); se omite el respaldo previo"
fi

echo "==> Descargando la imagen ${IMAGE_TAG}"
docker compose -f "$COMPOSE_FILE" pull app queue-worker scheduler

echo "==> Construyendo la imagen de nginx (assets ya compilados dentro de la imagen de la app)"
docker compose -f "$COMPOSE_FILE" build nginx

echo "==> Levantando servicios de datos (postgres/redis/minio) si no estan corriendo"
docker compose -f "$COMPOSE_FILE" up -d postgres redis minio

echo "==> Ejecutando migraciones (paso controlado, antes de reemplazar contenedores de la app)"
docker compose -f "$COMPOSE_FILE" run --rm app php artisan migrate --force

echo "==> Reemplazando contenedores de la aplicacion"
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans

echo "==> Optimizando (cache de config/rutas/vistas y descubrimiento de paquetes)"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan optimize

echo "==> Verificando salud de la aplicacion"
ATTEMPTS=0
until curl --silent --fail http://localhost/up > /dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge 10 ]; then
        echo "La aplicacion no respondio saludable en http://localhost/up tras el despliegue." >&2
        echo "Revisar logs: docker compose -f ${COMPOSE_FILE} logs app nginx" >&2
        exit 1
    fi
    sleep 3
done

echo "==> Despliegue completo: ${IMAGE_TAG} corriendo y saludable."
