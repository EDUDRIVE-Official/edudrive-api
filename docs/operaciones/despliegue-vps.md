# Despliegue en un VPS único (Contabo)

Guía de aprovisionamiento inicial y despliegue del MVP en un único VPS,
usando `compose.prod.yaml`. Complementa el runbook ya documentado en
`docs/operaciones/ci-cd.md` (que este despliegue automatiza vía
`scripts/deploy.sh`) y la matriz de ambientes de
`docs/operaciones/ambientes.md`.

Servidor de referencia: VPS Contabo `vmi3358465` (Cloud VPS 10 SSD,
región US-central), IP `207.244.24.115`, usuario `root`. Sustituir por los
datos reales si el servidor cambia.

## 1. Aprovisionamiento inicial del VPS

Estos pasos se ejecutan **una sola vez**, por SSH contra el servidor.

1. Conectarse: `ssh root@207.244.24.115`.
2. Actualizar el sistema: `apt update && apt upgrade -y`.
3. Instalar Docker y Docker Compose (plugin), siguiendo la guía oficial de
   Docker para Debian/Ubuntu:
   ```bash
   curl -fsSL https://get.docker.com | sh
   ```
   Esto instala tanto el daemon de Docker como el plugin `docker compose`.
4. Activar el firewall gratuito de Contabo (panel web de Contabo →
   `Network Services` → `Firewall`) permitiendo únicamente:
   - Puerto 22 (SSH).
   - Puerto 80 (HTTP — todavía no hay dominio ni TLS, ver sección 4).
   - Puerto 443 (HTTPS, para cuando exista un dominio).
   Todo lo demás (5432, 6379, 9000, 9001, etc.) permanece cerrado al
   exterior: en `compose.prod.yaml` esos servicios no exponen puertos al
   host, solo son alcanzables entre contenedores.
5. Clonar el repositorio en el servidor (se necesita el código fuente
   porque el servicio `nginx` de `compose.prod.yaml` se construye
   localmente desde `docker/php/Dockerfile`, no se descarga una imagen ya
   construida para él):
   ```bash
   git clone https://github.com/EDUDRIVE-Official/edudrive-api.git
   cd edudrive-api
   ```
6. Autenticarse contra el registro de imágenes (para poder hacer `docker
   compose pull` de `app`/`queue-worker`/`scheduler`, que sí son la imagen
   ya publicada por CI). Se necesita un Personal Access Token de GitHub
   con permiso `read:packages`:
   ```bash
   echo "<token>" | docker login ghcr.io -u <usuario-de-github> --password-stdin
   ```
7. Crear el archivo de entorno real a partir de la plantilla y completar
   los valores en blanco (`APP_KEY`, `DB_PASSWORD`,
   `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`) — ver sección 2.

## 2. Completar `.env`

```bash
cp .env.production.example .env
```

Luego editar `.env` y completar como mínimo:

- `APP_KEY`: generar una real. La forma más simple es pedírsela a la
  propia imagen ya publicada, sin necesitar PHP instalado en el VPS:
  ```bash
  docker run --rm ghcr.io/edudrive-official/edudrive-api:latest php -r "echo 'base64:' . base64_encode(random_bytes(32));"
  ```
- `DB_PASSWORD`: una contraseña real, por ejemplo `openssl rand -base64 32`.
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`: credenciales para el
  MinIO incluido en `compose.prod.yaml` (el mismo par de valores
  configura tanto Laravel como el propio contenedor de MinIO — no hace
  falta un proveedor S3 externo para el MVP). Cualquier cadena aleatoria
  sirve, por ejemplo `openssl rand -hex 20`.

`RequiredSecretsValidator` (`Modules\Foundation`) impide que la
aplicación arranque en producción si falta alguno de estos valores — es
una verificación real, no solo documentación.

## 3. Primer despliegue

Con Docker instalado, el `.env` completado, y el login a `ghcr.io` hecho:

```bash
./scripts/deploy.sh <sha-corto-de-la-imagen>
```

El SHA corto es el de un commit ya mergeado a `main` (cada push a `main`
dispara `build-image` en CI, que publica esa imagen). Se puede consultar
en GitHub → pestaña "Actions" del workflow, o con
`git rev-parse --short <commit>` localmente.

El script automatiza exactamente el runbook de `docs/operaciones/ci-cd.md`:
respalda la base de datos (si ya había una aplicación corriendo), descarga
la imagen, construye la imagen de `nginx`, corre las migraciones de forma
controlada, reemplaza los contenedores, cachea configuración/rutas/vistas,
y verifica `http://localhost/up` antes de darse por terminado.

## 4. Verificación

Desde cualquier máquina (no hace falta estar en el VPS):

```bash
curl http://207.244.24.115/up
```

Debe responder `200`. Luego, desde un navegador, entrar a
`http://207.244.24.115/login` y probar el flujo completo (crear un
superadministrador siguiendo el mismo procedimiento que en desarrollo:
`docker compose -f compose.prod.yaml exec app php artisan tinker`, o el
comando `authorization:assign-role` sobre un usuario ya registrado).

## 5. Dominio y TLS

Este despliegue inicial corre solo por HTTP (puerto 80) sobre la IP del
VPS — todavía no hay dominio decidido. Cuando exista uno:

1. Apuntar un registro DNS tipo `A` del dominio hacia `207.244.24.115`.
2. Reemplazar `APP_URL` y `CORS_ALLOWED_ORIGINS` en `.env` por el dominio
   real (con `https://`).
3. Agregar TLS. La forma más simple, sin reescribir `compose.prod.yaml`
   por completo, es poner un proxy con TLS automático (por ejemplo Caddy)
   delante del servicio `nginx` actual, o sustituir `nginx` directamente
   por Caddy con un `Caddyfile` de dos líneas (`tu-dominio { reverse_proxy
   app:9000 }` más el manejo de PHP-FPM). Esta migración queda fuera de
   alcance de este documento hasta que exista un dominio real que probar.

## 6. Actualizar el propio VPS con cambios de esta guía o de `compose.prod.yaml`

Si el repositorio clonado en el VPS queda desactualizado respecto a
`compose.prod.yaml`, `docker/nginx/default.conf` o este mismo documento,
actualizar el checkout antes de desplegar:

```bash
git pull
```

## Fuera de alcance (documentado explícitamente)

- Alta disponibilidad / múltiples réplicas (`compose.prod.yaml` asume un
  único VPS, un único contenedor por servicio).
- TLS automático ya configurado (ver sección 5 — depende de tener un
  dominio real primero).
- Backups automáticos fuera del propio VPS (los volúmenes con datos
  siguen viviendo en el mismo servidor; `php artisan backup:database` ya
  existente de ENG-084 respalda a `storage/`, dentro del volumen
  `edudrive-storage`, no a un destino externo).
- Despliegue automático desde CI (decisión explícita del usuario: disparo
  manual, para no guardar credenciales SSH de un servidor real en GitHub
  Actions).
