# ENG-082 — Scheduler: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** reducido (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

El roadmap solo trae cinco viñetas sueltas sin documento de diseño: Limpieza de
tokens, Notificaciones, Reportes programados, Expiración, Mantenimiento. La
investigación (un agente en background) encontró:

- **Ya existe un `Schedule::command('identity:purge-inactive-accounts')->daily()`**
  en `routes/console.php`, pero **nada ejecuta `schedule:run`/`schedule:work`**
  en `compose.yaml` — exactamente el mismo tipo de gap que ENG-081 resolvió
  para colas (Redis configurado, sin worker). Esa única entrada de scheduler
  es hoy inerte.
- **Limpieza de tokens**: Sanctum ya trae de fábrica el comando
  `sanctum:prune-expired` (confirmado con `php artisan list`), no usado en
  ningún lado. No hace falta construir nada nuevo — solo programarlo.
- **Mantenimiento**: `ExportFileWriter` (ENG-062/081) genera una URL firmada
  de 15 minutos pero **nunca borra el objeto subyacente** en MinIO/S3 — los
  archivos de exportación quedan huérfanos indefinidamente. `AsyncJob`
  (ENG-081) tampoco tiene ningún mecanismo de purga de registros antiguos
  completados/fallidos.
- **Notificaciones** (`NotificationFrequency::Daily/Weekly`) y **Reportes
  programados** (cron sobre `Modules\Analytics`) y **Expiración proactiva**
  (avisar antes de que venza un `Certificate`/`ApiConsumer`) son todas
  features de negocio nuevas (agregación temporal, ventanas de digest,
  decisiones de producto sobre cuándo/cómo notificar) — no solo activación de
  infraestructura ya presente. Quedan fuera de este alcance reducido.

## Decisiones de diseño

### A. Activar el scheduler real

- Nuevo servicio `scheduler` en `compose.yaml`: misma imagen que `app`,
  comando `php artisan schedule:work`, `depends_on: postgres/redis healthy`
  — mismo patrón exacto que el servicio `queue-worker` de ENG-081.
- `routes/console.php` gana dos entradas nuevas junto a la ya existente:
  `Schedule::command('sanctum:prune-expired --hours=24')->daily();` y
  `Schedule::command('async-processing:cleanup')->daily();` (ver C).

### B. Limpieza de tokens

Sin código nuevo: se aprovecha el comando nativo de Sanctum
`sanctum:prune-expired --hours=24`, programado diariamente vía A. No se
reimplementa nada que el framework ya resuelve.

### C. Mantenimiento (archivos de exportación huérfanos + `AsyncJob`s viejos)

- Los tres Jobs de exportación de ENG-081 (`ExportAuditLogsJob`,
  `ExportCoursesJob`, `ExportEnrollmentsJob`) agregan `storage_path` al
  `result` del `AsyncJob` que completan (además de `url`/`expires_at`/
  `row_count`/`format` ya existentes) — es el dato interno necesario para
  poder borrar el objeto después, distinto de la URL firmada temporal.
- `Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository` gana dos
  métodos: `allCompletedOrFailedBefore(DateTimeImmutable $threshold): array`
  (trae cualquier `AsyncJob` en estado terminal —Completed o Failed— más
  antiguo que el umbral, sin filtrar por `type`, ya que el propósito es
  purga general) y `delete(AsyncJobId $id): void`.
- Nuevo comando `Modules\AsyncProcessing\Presentation\Console\CleanupAsyncJobsCommand`
  (`async-processing:cleanup`): lee `config('async_processing.retention_hours', 24)`,
  obtiene los `AsyncJob`s terminales más antiguos que ese umbral; para cada
  uno cuyo `type` empiece con `'export.'`, esté `Completed` y tenga
  `result['storage_path']`, borra el archivo vía `FileStorage::delete()`
  antes de purgar el propio registro. Los `AsyncJob`s de otros tipos
  (`import.*`, `analytics.*`) solo se purgan de la tabla — no tienen archivo
  asociado que limpiar.
- Nueva config `config/async_processing.php` (`retention_hours`, env
  `ASYNC_JOB_RETENTION_HOURS`, default 24 — deliberadamente mayor a los 15
  minutos de vida de la URL firmada, para no competir con una descarga en
  curso ni con la ventana de sondeo del cliente).

## Fuera de alcance (documentado explícitamente)

- Digest real de notificaciones (`NotificationFrequency::Daily/Weekly`)
  — la preferencia ya existe en el dominio, pero implementarla requiere
  diseñar la lógica de agregación y ventanas, fuera de este alcance reducido.
- Programación periódica de reportes de `Modules\Analytics` — el mecanismo
  bajo demanda de ENG-081 se mantiene tal cual.
- Barrido proactivo de expiración de `Certificate`/`ApiConsumer` (marcar,
  revocar o notificar antes de vencer) — ambas siguen siendo invariantes
  evaluadas perezosamente en request-time, decisión ya correcta y suficiente
  para esta historia.

## Plan de verificación

Mismo ritmo establecido: Pest (Docker), Pint, PHPStan (`--memory-limit=512M`)
sobre los módulos tocados y luego sobre el repo completo antes del cierre.
