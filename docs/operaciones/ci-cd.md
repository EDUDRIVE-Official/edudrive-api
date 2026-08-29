# CI/CD

Documento operativo derivado de ENG-086 (CI/CD). Ver
`docs/plans/2026-08-29-ci-cd-eng086-design.md` para el diseño técnico
completo. Cubre lo que **sí** está automatizado (`.github/workflows/ci.yml`)
y el procedimiento manual (runbook) para lo que, a la fecha, no tiene un
servidor real de destino contra el cual automatizarse.

## Lo que está automatizado

`.github/workflows/ci.yml`, disparado en cada `push`/`pull_request`:

- **`quality`**: `composer format:test` (Pint) + `composer analyse`
  (Larastan/PHPStan).
- **`test`**: `composer test` (Pest, suite completa `tests/` + `modules/`).
- **`build-image`** (solo en `push` a `main`, y solo si los dos jobs
  anteriores pasan): construye `docker/php/Dockerfile` — el mismo Dockerfile
  usado en desarrollo, deliberadamente no duplicado en una variante "de
  producción" para que ambos no diverjan — y lo publica en
  `ghcr.io/edudrive-official/edudrive-api` con dos tags: el SHA corto del
  commit (inmutable, es la referencia exacta para un rollback) y `latest`.
  Usa el `GITHUB_TOKEN` nativo del workflow, sin secretos nuevos que
  configurar.

La suite de Pest corre contra SQLite en memoria (ver `phpunit.xml`), por lo
que el pipeline **no** necesita levantar Postgres/Redis/MinIO reales para
validar el código.

## Runbook manual: migraciones, despliegue y rollback

No existe, a la fecha, ningún servidor, orquestador ni proveedor de hosting
real decidido para este proyecto (ver `docs/operaciones/ambientes.md`).
Automatizar estos tres pasos contra un destino inventado sería construir
infraestructura no verificable, así que quedan documentados como
procedimiento manual, listo para conectarse a un workflow real el día que
exista una decisión de infraestructura.

### Migraciones controladas

Las migraciones **no** se ejecutan automáticamente al iniciar el
contenedor (`docker/php/Dockerfile` termina en `CMD ["php-fpm"]`, sin
entrypoint que las invoque) — hacerlo sería riesgoso con múltiples réplicas
o despliegues rolling ejecutando `migrate` a la vez. Procedimiento manual:

1. Respaldar la base de datos antes de migrar: `php artisan backup:database`
   (ENG-084).
2. Ejecutar la migración como paso deliberado y separado, antes de
   reemplazar los contenedores de la aplicación: `php artisan migrate --force`.

### Despliegue

Con la imagen ya publicada por el job `build-image`:

1. Extraer la imagen por su tag de SHA inmutable (no `latest`, para que el
   despliegue sea reproducible y auditable):
   `docker pull ghcr.io/edudrive-official/edudrive-api:<sha>`.
2. Ejecutar el paso de migración controlada (sección anterior).
3. Reemplazar los contenedores en ejecución con la nueva imagen.
4. Verificar salud de la aplicación tras el reemplazo.

Automatizar este procedimiento contra un servidor real queda pendiente de
una decisión de infraestructura (qué proveedor, qué orquestador) que no
existe hoy en el proyecto.

### Rollback

Cada imagen queda taggeada por su SHA inmutable en GHCR, así que el
rollback es desplegar de nuevo el tag de SHA anterior conocido como bueno
(mismos pasos 2-4 de "Despliegue", con la imagen anterior). Si el incidente
involucra una migración de base de datos que deba revertirse, se restaura
con `php artisan backup:restore {path}` (ENG-084) usando el respaldo tomado
inmediatamente antes de esa migración.

## Fuera de alcance (documentado explícitamente)

- Cualquier workflow de despliegue/rollback automatizado contra un
  servidor real (SSH, Kubernetes, un PaaS).
- Secrets de GitHub Actions para credenciales de un servidor de destino.
- Un `Dockerfile` de producción separado del de desarrollo.
- Cualquier comando Artisan nuevo de "migración segura" — el procedimiento
  usa las herramientas que ya existen (`backup:database`, `migrate --force`).
