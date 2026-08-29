# ENG-086 — CI/CD: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** reducido (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

La historia trae siete viñetas: Pint, Larastan, Pest, Construcción de
imágenes, Migraciones controladas, Despliegue, Rollback. Un agente en
background investigó el estado actual (sin modificar nada) y confirmó:

- **No existe ningún workflow de CI/CD propio del proyecto** — no hay
  `.github/workflows/*.yml` en el repo (solo los de dependencias de
  terceros dentro de `vendor/`/`node_modules/`, irrelevantes).
- **No existe ningún proveedor de hosting, registro de contenedores ni
  servidor de destino real** en ningún documento del proyecto. Las únicas
  menciones a "AWS" son sobre el SDK S3 usado contra MinIO local
  (`docs/plans/2026-08-27-gestion-archivos-eng060-design.md`), no un
  despliegue real. `docs/operaciones/ambientes.md` (ENG-085) lista los 5
  ambientes previstos sin proveedor concreto, y documenta explícitamente
  que "no existen archivos `compose.*.yaml` ni `.env.*.example` separados
  por ambiente".
- **No existen scripts de despliegue ni de rollback** en ninguna parte
  del repo (`/deploy`, `/scripts`, `/.deploy`: ninguno existe).
- **Las migraciones no están automatizadas en el ciclo de vida del
  contenedor**: `docker/php/Dockerfile` termina en `CMD ["php-fpm"]` sin
  ejecutar migraciones; `compose.yaml` no tiene entrypoint que las
  invoque. Las únicas referencias a `artisan migrate` están en los
  scripts `setup`/`post-create-project-cmd` de `composer.json`, pensados
  para bootstrap local manual.
- **Los scripts de calidad ya existen y están listos para conectarse a
  CI**: `composer.json` define `format:test` (Pint), `analyse` (Larastan/
  PHPStan, `--memory-limit=1G`) y `test` (`artisan config:clear` + `artisan
  test tests modules`). La suite de Pest corre contra SQLite en memoria
  (`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
  `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`), por lo que **no necesita
  Postgres/Redis/MinIO reales para ejecutarse en CI**.
- El remoto real del repo es `github.com/EDUDRIVE-Official/edudrive-api`,
  lo que hace de GitHub Actions + GHCR (`ghcr.io`) la elección natural sin
  necesidad de credenciales nuevas (usa el `GITHUB_TOKEN` nativo del
  workflow).

Dado que no existe ningún servidor/registro/credencial de destino real
decidido en el proyecto, automatizar "Despliegue" y "Rollback" contra un
destino inventado sería construir infraestructura hipotética no
verificable — contrario al principio de no diseñar para requisitos
hipotéticos. El usuario eligió el alcance reducido recomendado: CI real +
construcción/publicación de imagen (ambas verificables dentro de este
repo) y un runbook documentado para migraciones/despliegue/rollback (sin
automatizarlos contra un destino que no existe).

## Decisiones de diseño

### A. `.github/workflows/ci.yml` — Pint + Larastan + Pest

Un único workflow, disparado en `push` y `pull_request`. Dos jobs:

- **`quality`**: checkout, `shivammathur/setup-php` con PHP 8.4 (misma
  versión que `docker/php/Dockerfile`) y las extensiones que el proyecto
  realmente usa (`bcmath, gd, intl, opcache, pcntl, pdo_sqlite, pdo_pgsql,
  redis, zip, mbstring, xml`), `composer install --prefer-dist
  --no-progress`, copia `.env.example` a `.env` y `artisan key:generate`
  (la suite de tests no fija `APP_KEY` en `phpunit.xml`), luego
  `composer format:test` y `composer analyse`.
- **`test`**: mismos pasos de setup, luego `composer test`. Job separado
  del anterior (en paralelo) para que un fallo de Pint no oscurezca un
  fallo de tests en la misma tanda de logs.

No se agregan servicios de Postgres/Redis/MinIO al workflow — la suite
completa (`tests/` + `modules/*/Tests/`) corre contra SQLite en memoria
según confirma `phpunit.xml`, igual que ya corre localmente.

### B. Construcción y publicación de imagen

Nuevo job **`build-image`** en el mismo workflow, con `needs: [quality,
test]` y condición `if: github.event_name == 'push' && github.ref ==
'refs/heads/main'` (no se publican imágenes por cada PR, solo cuando algo
llega a `main`). Usa `docker/build-push-action` sobre
`docker/php/Dockerfile` (el mismo Dockerfile ya usado en desarrollo, sin
duplicar uno "de producción" — evita que diverjan), publicando en
`ghcr.io/edudrive-official/edudrive-api` con dos tags: el SHA corto del
commit (`ghcr.io/.../edudrive-api:<sha>`, inmutable, referencia exacta
para rollback) y `latest` (referencia móvil de conveniencia). Login vía
`docker/login-action` con `${{ secrets.GITHUB_TOKEN }}` — no requiere
ningún secreto nuevo.

### C. Runbook de migraciones, despliegue y rollback

Nuevo documento `docs/operaciones/ci-cd.md`, sin código de automatización
nueva (no hay servidor real contra el cual automatizar). Cubre:

- **Migraciones controladas**: por qué deliberadamente no se ejecutan
  automáticamente al iniciar el contenedor (riesgo de condiciones de
  carrera con múltiples réplicas/despliegues rolling ejecutando `migrate`
  a la vez); procedimiento manual recomendado — respaldo previo
  (`backup:database`, ENG-084) seguido de `artisan migrate --force` como
  paso deliberado y separado, antes de reemplazar los contenedores de la
  aplicación.
- **Despliegue**: procedimiento manual con la imagen ya publicada por el
  job `build-image` — extraer la imagen por su tag de SHA inmutable,
  ejecutar el paso de migración controlada, reemplazar los contenedores
  en ejecución, verificar salud (`GET /up` o equivalente). Se documenta
  explícitamente que automatizar este paso contra un servidor real queda
  pendiente de una decisión de infraestructura (qué proveedor, qué
  orquestador) que no existe hoy en el proyecto.
- **Rollback**: dado que cada imagen queda taggeada por SHA inmutable en
  GHCR, el rollback es desplegar de nuevo el tag de SHA anterior conocido
  como bueno. Si el incidente involucra una migración de base de datos que
  deba revertirse, se usa `backup:restore` (ENG-084) contra el respaldo
  tomado inmediatamente antes de esa migración.

## Fuera de alcance (documentado explícitamente)

- Cualquier workflow de despliegue/rollback automatizado contra un
  servidor real (SSH, Kubernetes, un PaaS) — no existe ningún destino
  decidido en el proyecto; construir eso ahora sería infraestructura
  hipotética no verificable.
- Secrets de GitHub Actions para credenciales de un servidor de destino
  (no hay servidor).
- Un `Dockerfile` de producción separado del de desarrollo — se reutiliza
  `docker/php/Dockerfile` deliberadamente para que no diverja del entorno
  ya probado.
- Cualquier nuevo comando Artisan de "migración segura" — el
  procedimiento queda documentado como runbook manual, ejecutable con las
  herramientas que ya existen (`backup:database`, `migrate --force`).

## Plan de verificación

Pint y PHPStan (`--memory-limit=512M`) sobre los archivos tocados (no
aplica a YAML, pero sí a cualquier archivo PHP si lo hubiera). El workflow
de GitHub Actions se valida por sintaxis (YAML) y, si el usuario decide
hacer push de la rama, por su ejecución real en GitHub — no se puede
ejecutar `act`/GitHub Actions localmente contra Docker-in-Docker en este
entorno, así que la verificación final de que el pipeline corre de
verdad queda pendiente de un push real, fuera del control de este agente.
