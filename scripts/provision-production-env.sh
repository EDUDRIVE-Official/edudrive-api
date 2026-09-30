#!/usr/bin/env bash

set -euo pipefail

TARGET_ENV="${1:-.env}"

if [ -e "$TARGET_ENV" ]; then
    echo "El archivo $TARGET_ENV ya existe; no se sobrescribio." >&2
    exit 1
fi

IFS= read -r POSTMARK_TOKEN

if [ -z "$POSTMARK_TOKEN" ]; then
    echo 'No se recibio el token de Postmark.' >&2
    exit 1
fi

umask 077

APP_KEY_VALUE="base64:$(openssl rand -base64 32)"
DB_PASSWORD_VALUE="$(openssl rand -hex 32)"
S3_ACCESS_KEY_VALUE="$(openssl rand -hex 16)"
S3_SECRET_KEY_VALUE="$(openssl rand -hex 32)"

printf '%s\n' \
    'APP_NAME=EDUDRIVE' \
    'APP_ENV=production' \
    "APP_KEY=$APP_KEY_VALUE" \
    'APP_DEBUG=false' \
    'APP_DOMAIN=app.edudrive.vr506.com' \
    'APP_URL=https://app.edudrive.vr506.com' \
    'LOG_CHANNEL=stack' \
    'LOG_LEVEL=warning' \
    'IMAGE_TAG=bootstrap' \
    'DB_CONNECTION=pgsql' \
    'DB_HOST=postgres' \
    'DB_PORT=5432' \
    'DB_DATABASE=edudrive' \
    'DB_USERNAME=edudrive' \
    "DB_PASSWORD=$DB_PASSWORD_VALUE" \
    'REDIS_HOST=redis' \
    'REDIS_PORT=6379' \
    'CACHE_STORE=redis' \
    'SESSION_DRIVER=redis' \
    'SESSION_SECURE_COOKIE=true' \
    'QUEUE_CONNECTION=redis' \
    'FILESYSTEM_DISK=s3' \
    "AWS_ACCESS_KEY_ID=$S3_ACCESS_KEY_VALUE" \
    "AWS_SECRET_ACCESS_KEY=$S3_SECRET_KEY_VALUE" \
    'AWS_DEFAULT_REGION=us-east-1' \
    'AWS_BUCKET=edudrive' \
    'AWS_ENDPOINT=http://minio:9000' \
    'AWS_USE_PATH_STYLE_ENDPOINT=true' \
    'MAIL_MAILER=postmark' \
    "POSTMARK_API_KEY=$POSTMARK_TOKEN" \
    'MAIL_FROM_ADDRESS=noreply@edudrive.vr506.com' \
    'MAIL_FROM_NAME=EDUDRIVE' \
    'MAIL_DELIVERY_MODE=external' \
    'CORS_ALLOWED_ORIGINS=https://app.edudrive.vr506.com' \
    > "$TARGET_ENV"

unset POSTMARK_TOKEN

echo "Entorno de produccion creado en $TARGET_ENV con permisos restringidos."
