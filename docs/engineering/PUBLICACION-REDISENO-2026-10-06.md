# Publicación del rediseño visual y las etapas E1–E4 — 6 de octubre de 2026

Autorización: el usuario solicitó actualizar la app en línea y aprobó expresamente (1) publicar `main` tras integrar la revisión con `super_admin`, (2) aplicar la migración aditiva `learning_purpose` con respaldo y restauración de prueba previos y (3) revisar el plan antes de activar; luego dio el visto bueno final.
Versión activa: `rediseno-vial-20261006r1`, activada a las **16:46:08 UTC / 10:46:08 Costa Rica**.
URL: https://app.edudrive.vr506.com.

## Alcance y trazabilidad

- Código: commit `ed1c5f807cb8c5a25ce00e59a8125b3e95888583` de `main` ([PR #9](https://github.com/EDUDRIVE-Official/edudrive-api/pull/9)). Incluye los PR #2 a #9: recorridos curriculares E1–E4 y propósito 17+, las cuatro fases del rediseño «Vial vibrante» y la revisión con `super_admin`. CI del commit: Pest y Pint + Larastan aprobados.
- A diferencia de las entregas del 29 de septiembre, esta se construyó desde un **`git archive` del commit exacto** (sin cambios locales ni archivos sin versionar). Paquete `source.tar.gz`, SHA256 `2ee6b243386198b1cfb582c6e65781b7b5eefb3c2a0e8da7d4d76dce75ed96ff`; no contiene `.env` reales, solo las plantillas con secretos vacíos.
- Las imágenes se construyeron en el propio servidor con `docker/php/Dockerfile` (objetivos `nginx` y `app`; el Dockerfile ejecuta `npm ci` y `npm run build`, de modo que las fuentes Barlow autoalojadas quedan en la imagen):
  - App, worker y scheduler: `edudrive-api:rediseno-vial-20261006r1`, imagen `sha256:43dba1a58d58ec8b7e752f986b3357146e1d9a1a60ca6b625f2944ee74528b36`.
  - Nginx: `edudrive-nginx:rediseno-vial-20261006r1`, imagen `sha256:a23cad91fea7faf8a2d3bbde6d4ba4df63701235aa71d14f6aa7db1a05ce25cf`.
- Entrega: `/opt/edudrive/releases/rediseno-vial-20261006r1` (`publication.json`, `activated-at.txt`, `before.dump`, `before.dump.sha256`, `restore-test.sh`, `restore-check.php`, `restore-test.log`, `compose.before.yaml`, `compose.next.yaml`, registros de construcción).
- Se conservaron `.env`, volúmenes, zona horaria y la revisión DESCUBRO restringida a `super_admin` (`DESCUBRO_PRODUCTION_REVIEW_ENABLED=true` en el override). No hubo seeders.
- **Migración:** exactamente una, `2026_09_30_000001_add_learning_purpose_to_student_profiles` (columna `learning_purpose` opcional en `student_profiles`). Producción tenía 71 de 72 migraciones aplicadas y ninguna otra pendiente. En ese momento la base tenía **5 usuarios y 0 perfiles de estudiante**.

## Respaldo y validación previa

- Respaldo `before.dump` (519 115 bytes, 91 tablas con datos), SHA256 `69d4314e0c421731a8e436481b4b9efa75880b946d9a3f2758e1d20146aa66eb`, acceso restringido.
- Restauración completa con `pg_restore --exit-on-error --no-owner` en un PostgreSQL 17 temporal, en una red Docker **interna** (sin acceso a Internet), con caché, sesiones, cola y correo desactivados. Sin errores. Con la imagen candidata se aplicó la migración en la copia (9 ms) y se comprobó que la columna existe.
- Con la imagen candidata sobre la copia, el kernel HTTP respondió **200** en 13 rutas (`/login`, `/mi-perfil`, `/courses`, ficha de curso, pasaporte, notificaciones, progreso, organizaciones, usuarios, sistema, analítica, asignar roles y DESCUBRO) y las vistas mostraron las marcas del rediseño. Son comprobaciones del kernel con un administrador de la copia, no navegación visual.
- Se retiraron el contenedor y la red temporales; el respaldo recuperable queda en la entrega.

## Activación y comprobaciones posteriores

1. Migración aplicada en producción con la imagen nueva **antes** de reemplazar contenedores (compatible con el código anterior): 11,81 ms; sin migraciones pendientes después.
2. Recreados únicamente app, worker, scheduler y nginx con `compose.next.yaml` (`up -d --no-deps --no-build`); Postgres, Redis y MinIO no se tocaron. `php artisan optimize` sin errores.
3. El override activo `/opt/edudrive/compose.descubro-review.yaml` se reemplazó por el nuevo; el anterior queda en `compose.before.yaml`. Una activación repetida en simulación no recrea ningún contenedor.
4. Por HTTPS: `/up` y `/login` **200**; `/mi-perfil`, `/courses` y `/admin/sistema` **302** al ingreso para visitante anónimo. `/login` ya sirve el contenido nuevo (`ed-ingreso`, «Un recorrido para cada etapa», `autocomplete`). La hoja de estilos y la fuente `barlow-latin-700` se sirven con tipo correcto (`text/css`, `font/woff2`).
5. Navegador (Edge sin cabeza) sobre `/login` público, escritorio y celular: diseño nuevo, sin desborde, sin errores de consola y con Barlow 400–700 y Barlow Condensed 800 cargadas. Capturas en la copia de trabajo (`propuestas-diseno/produccion-20261006/`, sin versionar).
6. Registros de app y nginx sin errores ni respuestas 5xx tras la activación; nginx `healthy`.

## Límites y avisos

- **No hubo navegación autenticada en producción**: no se contó con una sesión de prueba allí. Las pantallas autenticadas se comprobaron solo en la copia restaurada (kernel HTTP) y antes, en local.
- No se ejecutaron acciones de escritura de usuarios ni flujos completos en producción.
- El `queue-worker` se reinicia aproximadamente cada hora (159 reinicios en unos 6,6 días, con código de salida 0): es el comportamiento esperado de un worker con límite de tiempo, no una caída.
- **Defecto latente en `scripts/deploy.sh`:** verifica la salud con `wget http://localhost/up` dentro del contenedor nginx, pero nginx escucha solo en IPv4 y `localhost` resuelve primero a IPv6, así que esa comprobación falla aunque el servicio esté sano (el healthcheck del contenedor usa `127.0.0.1` y pasa). No se usó el script; conviene corregirlo antes de usarlo.
- Las imágenes de entregas anteriores a `editorial-approved-20260929r1` no existen en el servidor; solo las de esa versión se conservan.

## Reversión

Revertir el código no restaura datos. La columna `learning_purpose` puede quedar: el código anterior la ignora. Para volver a la versión anterior, desde `/opt/edudrive`:

```sh
cp releases/rediseno-vial-20261006r1/compose.before.yaml compose.descubro-review.yaml
docker compose -f compose.prod.yaml -f compose.bootstrap.yaml -f compose.descubro-review.yaml up -d --no-deps --no-build app queue-worker scheduler nginx
docker compose -f compose.prod.yaml -f compose.bootstrap.yaml -f compose.descubro-review.yaml exec -T app php artisan optimize
```

Imágenes conservadas para ese regreso: `edudrive-api:editorial-approved-20260929r1` (`sha256:3398e960e160…`) y `edudrive-nginx:editorial-approved-20260929r1` (`sha256:af48146799d4…`). **No borrarlas** (`docker image prune`, `docker system prune`) mientras sirvan de recuperación. No restaurar `before.dump` encima de producción sin evaluar las escrituras posteriores a su fecha.
