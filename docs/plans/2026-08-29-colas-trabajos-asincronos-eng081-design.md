# ENG-081 — Colas y trabajos asíncronos: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** completo (elegido por el usuario sobre el reducido recomendado).

## Contexto y hallazgos de la investigación

El roadmap solo trae seis viñetas sueltas sin documento de diseño: Redis, Correos,
Exportaciones, Procesamiento de archivos, Analítica, Reintentos. La investigación
previa (dos agentes en background) encontró:

- **Redis** ya está completamente configurado (`config/queue.php`, `config/database.php`,
  contenedor `redis:7-alpine` en `compose.yaml`) pero nada lo usa: `QUEUE_CONNECTION=database`,
  `CACHE_STORE=database`. No existe ningún worker de cola (`queue:work`) en `compose.yaml`.
  Este gap está documentado explícitamente dos veces en el repo
  (`docs/plans/2026-08-27-exportaciones-eng062-design.md` y `ENG-LOG.md` IMP-074) como la
  razón por la que ENG-062 y ENG-074 mantuvieron ciertas operaciones síncronas.
- **Correos**: `NotificationChannel::Email` es solo un valor de enum sin ninguna rama de
  efecto en `SendNotificationHandler`. No existe ningún `Mailable` ni uso de `Mail::` en
  todo el repo. Mailpit ya está provisionado en `compose.yaml` pero no se usa.
- **Exportaciones**: `ExportAuditLogsHandler`, `ExportCoursesHandler`, `ExportEnrollmentsHandler`
  generan CSV de forma síncrona (misma request), reutilizando `CsvWriter`/`ExportFileWriter`/
  `ExportResponse` de `Modules\Foundation`. `ExportMyDataUseCase` (portabilidad de datos
  personales, un solo usuario) devuelve JSON directamente, no CSV — no es del mismo tipo de
  operación y se excluye de esta historia (ver "Fuera de alcance").
- **Procesamiento de archivos**: `BulkImportUsersUseCase`, `BulkImportCoursesHandler`,
  `BulkImportQuestionsHandler` reciben filas ya parseadas por el controlador (`rows: array`)
  y las procesan síncronamente en un `foreach` con captura de errores por fila.
- **Analítica**: no existe ningún módulo ni funcionalidad bajo ese nombre.
- **Reintentos**: solo dos jobs `ShouldQueue` existen hoy. `DeliverWebhookJob`
  (`Modules\Webhook`) implementa su propio backoff a nivel de dominio con `$tries = 1`
  (desactiva el reintento nativo a propósito — no se toca, es un diseño ya cerrado y
  documentado en ENG-074). `SendMobilePushJob` (`Modules\Mobile`) no define `$tries`,
  `backoff()` ni `failed()` — depende del comportamiento nativo por defecto sin ninguna
  configuración explícita.
- Los tests corren con `QUEUE_CONNECTION=sync` (`phpunit.xml`), por lo que cualquier
  `ShouldQueue` se ejecuta en línea dentro de la misma request/test, sin necesitar
  `Queue::fake()`.

## Decisiones de diseño

### A. Infraestructura de colas (Redis + worker + reintentos nativos estandarizados)

- `QUEUE_CONNECTION=redis` en `.env` y `.env.example` (la conexión `redis` en
  `config/queue.php` ya existe, solo se activa).
- **Corrección necesaria en `.env`**: `REDIS_HOST=127.0.0.1` no es alcanzable desde el
  contenedor `app` — el servicio se llama `redis` en la red `edudrive-network` de
  `compose.yaml`. Se cambia a `REDIS_HOST=redis` en ambos archivos.
- Nuevo servicio `queue-worker` en `compose.yaml`: misma imagen que `app` (mismo `build`),
  comando `php artisan queue:work redis --sleep=3 --max-time=3600`, `depends_on: redis
  (healthy), postgres (healthy)`. No se fijan `--tries`/`--backoff` a nivel de CLI porque
  cada Job nuevo de esta historia define su propio `$tries`/`backoff()`/`failed()` explícito
  (más legible y testeable que depender de flags del proceso worker).
- `SendMobilePushJob` gana `public int $tries = 3;`, `backoff(): array => [10, 30, 60]`,
  `failed(Throwable $e): void` (log del fallo final, sin efecto de dominio adicional — el
  intento de push ya es best-effort). `DeliverWebhookJob` no se toca: su `$tries = 1` y
  reintento a nivel de dominio es una decisión ya cerrada de ENG-074.

### B. Mecanismo genérico de trabajos asíncronos (`Modules\Foundation`)

En vez de crear un aggregate de seguimiento distinto para exportaciones, importaciones y
analítica (tres formas casi idénticas de "encolar algo, consultar su estado después"), se
introduce un único primitivo reutilizable en `Modules\Foundation`:

- `Domain\Aggregates\AsyncJob`: `id` (`AsyncJobId`, UUID), `type` (string libre, ej.
  `'export.enrollments'`, `'import.users'`, `'analytics.enrollments_summary'` — no un enum
  cerrado, porque cualquier módulo futuro debe poder declarar su propio tipo sin tocar
  Foundation), `requestedByUserId` (`?string`, **sin FK**, mismo patrón que
  `Modules\AiGovernance` — es metadato operativo, no un registro académico), `status`
  (`AsyncJobStatus`: Pending/Processing/Completed/Failed), `result` (`?array`, payload JSON
  libre interpretado por quien consulta según `type`), `failureReason` (`?string`),
  `createdAt`/`startedAt`/`completedAt`.
- Transiciones: `start()` (Pending→Processing), `complete(array $result, DateTimeImmutable)`
  (Processing→Completed), `fail(string $reason, DateTimeImmutable)` (Pending u
  Processing→Failed). Cualquier otra transición lanza `InvalidAsyncJobTransition`.
- `Domain\Repositories\AsyncJobRepository` + implementación Eloquent estándar + migración.
- `Application\Queries\GetAsyncJobQuery`/`GetAsyncJobHandler` + `AsyncJobResponse`
  (`id`/`type`/`status`/`result`/`failure_reason`/`created_at`/`started_at`/`completed_at`).
- **Autorización por propiedad, no por permiso**: `GET /api/v1/async-jobs/{asyncJobId}`
  (`auth:sanctum`) — el handler verifica que `requestedByUserId` coincida con el usuario
  autenticado; si no coincide o no existe, lanza `AsyncJobNotFound` (404, evita filtrar
  existencia). Así el mismo endpoint sirve tanto a un `SuperAdmin` consultando su propia
  exportación como a un `Student` consultando su propio reporte, sin acoplar Foundation a
  los permisos específicos de cada módulo consumidor.
- Cada módulo consumidor (Academic, Admin, Notification-adjacent, Analytics) solo necesita:
  crear un `AsyncJob` (Pending) + despachar su propio Job específico con el `asyncJobId`.
  El Job hace `start()` → ejecuta la lógica real (idéntica a la que ya existía en el
  handler síncrono) → `complete()`/`fail()`.

### C. Exportaciones asíncronas

`ExportAuditLogsHandler`, `ExportCoursesHandler`, `ExportEnrollmentsHandler` cambian de
devolver `ExportResponse` directamente a:

1. Crear y guardar un `AsyncJob` (`type: 'export.audit_logs'|'export.courses'|'export.enrollments'`,
   `requestedByUserId`: el actor autenticado).
2. Despachar `ExportAuditLogsJob`/`ExportCoursesJob`/`ExportEnrollmentsJob` (nuevos,
   `ShouldQueue`, `$tries = 3`, `backoff(): [10, 30, 60]`, `failed()` marca el `AsyncJob`
   como `Failed`) con el `asyncJobId`. Cada Job reutiliza exactamente la misma lógica de
   generación CSV que ya existía en el handler (mismas dependencias `CsvWriter`/
   `ExportFileWriter`/repositorio/`AuditLogger`), solo movida de "ejecutar inline" a
   "ejecutar dentro de `handle()` del Job".
3. El handler devuelve `AsyncJobResponse` (estado `pending`) y el controlador responde
   **202 Accepted** en vez de 200 con URL inmediata.
4. El cliente sondea `GET /api/v1/async-jobs/{id}` hasta `status: completed` (con `result.url`)
   o `status: failed` (con `failure_reason`).

**Cambio de contrato HTTP**: los tres endpoints (`POST .../courses/export`,
`.../enrollments/export`, `.../operations/audit-logs/export`) dejan de devolver la URL de
forma inmediata. Es un cambio de contrato deliberado, ya telegrafiado y aceptado por el
usuario al elegir "alcance completo" (la opción incluía explícitamente "mover todos los
imports/exports existentes a async").

### D. Procesamiento de archivos asíncrono

Mismo patrón que exportaciones para los tres imports masivos
(`BulkImportUsersUseCase`, `BulkImportCoursesHandler`, `BulkImportQuestionsHandler`):
el controlador sigue parseando el CSV subido de forma síncrona (ya acotado a 500 filas,
parsing en sí es barato), pero en vez de invocar el handler síncronamente crea un
`AsyncJob` (`type: 'import.users'|'import.courses'|'import.questions'`) y despacha
`ImportUsersJob`/`ImportCoursesJob`/`ImportQuestionsJob` con las filas ya parseadas +
`actorId`. El resultado final (`total`/`created`/`failed`/`results`) se guarda como
`result` del `AsyncJob` al completar — nunca falla el Job completo por una fila individual
inválida (mismo try/catch por fila que ya existía).

**Fuera de alcance explícito**: `CreateBulkEnrollmentsHandler` y
`CreateBulkInstitutionalEnrollmentsHandler` (matrícula masiva, ENG-061/ENG-076) no se
tocan — no parsean un archivo subido en el sentido de esta historia (reciben filas ya
construidas como parte de un flujo de negocio distinto y ya cerrado), y convertirlos
introduciría alcance no pedido sobre historias ya completadas.

### E. Correo real

- `Notification\Infrastructure\Mail\NotificationMail` (`Mailable`): texto plano con
  `subject`/`body`, `from` tomado de `config('mail.from')`.
- `Notification\Application\Services\EmailNotificationSender` (puerto): `send(string $userId,
  string $subject, string $body): void`.
- `Notification\Infrastructure\Services\QueuedEmailNotificationSender` (implementación):
  despacha `SendEmailNotificationJob::dispatch(...)` — mismo patrón exacto que
  `QueuedMobilePushSender`/`SendMobilePushJob` de ENG-075.
- `Notification\Infrastructure\Jobs\SendEmailNotificationJob` (`ShouldQueue`, `$tries = 3`,
  `backoff(): [10, 30, 60]`, `failed()` solo loguea): busca el usuario vía
  `UserRepository::findById()`; si no existe ya no falla el Job (usuario pudo eliminarse
  entre el envío y el procesamiento) — simplemente no hace nada, mismo criterio que
  `DeliverWebhookJob` al toparse con una suscripción inactiva.
- `SendNotificationHandler` gana la rama `elseif ($channel === NotificationChannel::Email)`
  llamando a `EmailNotificationSender::send(...)`, análoga a la rama `Mobile` existente.
- `NotificationServiceProvider` liga `EmailNotificationSender::class` a
  `QueuedEmailNotificationSender::class`.
- `.env`/`.env.example`: se activa Mailpit como mailer real de desarrollo
  (`MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`, sin usuario/contraseña) —
  mismo razonamiento que con Redis: la infraestructura ya estaba provisionada en
  `compose.yaml` sin consumirse.

### F. `Modules\Analytics` (nuevo, alcance acotado)

El roadmap no da ningún detalle de "Analítica" más allá de la palabra suelta. Se interpreta
de la forma más concreta y verificable posible, coherente con el resto de la historia
(reutilizar la infraestructura de colas recién construida) y sin invadir el territorio de
ENG-080 ("Análisis de patrones de conducción" — riesgos, tendencias, recomendaciones,
explicabilidad — **Diferido**, no se toca): un snapshot asíncrono de conteos reales ya
existentes en el sistema, sin ningún componente de aprendizaje automático o inferencia.

- `Domain\Enums\AnalyticsReportType`: `EnrollmentsSummary`, `CertificationsSummary`,
  `UsersSummary` — enum cerrado y deliberadamente pequeño (evita construir un framework de
  reportería genérico sin casos de uso reales que lo pidan).
- `Application\Commands\RequestAnalyticsReportCommand`/`RequestAnalyticsReportHandler`:
  valida el tipo (`InvalidAnalyticsReportType` si no coincide con el enum), crea un
  `AsyncJob` (`type: 'analytics.'.$type`) y despacha `GenerateAnalyticsReportJob`.
- `Infrastructure\Jobs\GenerateAnalyticsReportJob` (`ShouldQueue`, mismo patrón de
  `$tries`/`backoff`/`failed` que el resto): según el tipo, usa los repositorios ya
  existentes de otros módulos (`EnrollmentRepository::all()`, `CertificateRepository::all()`
  — método nuevo, ver abajo —, `UserRepository::all()`) y calcula conteos agrupados por
  estado (`array_count_values` sobre `->status()->value`) — sin nueva infraestructura de
  agregación, solo lectura de lo que ya existe.
- **Cambio pequeño en `Modules\Certification`**: `CertificateRepository` gana el método
  `all(): array` (análogo a como `CourseRepository`/`EnrollmentRepository`/`UserRepository`
  ya lo tienen) — hoy solo expone `allForUser()`, insuficiente para un conteo global.
- Presentation: un único endpoint `POST /api/v1/analytics/reports` (permiso nuevo
  `analytics.view`, otorgado solo a `Role::SuperAdmin`, mismo criterio que
  `ai_governance.*` en ENG-078) que dispara el cálculo y devuelve el `asyncJobId` (202). El
  resultado se consulta con el endpoint **genérico** `GET /api/v1/async-jobs/{id}` de la
  parte B — no se construye un endpoint de consulta propio para Analytics, validando que
  el mecanismo genérico es realmente reutilizable entre módulos.

## Fuera de alcance (documentado explícitamente)

- `ExportMyDataUseCase` (portabilidad de datos personales): no es CSV, es JSON de un solo
  usuario, ya acotado y síncrono — no encaja en el patrón de exportación async de esta
  historia.
- `CreateBulkEnrollmentsHandler`/`CreateBulkInstitutionalEnrollmentsHandler`: matrícula
  masiva, no "procesamiento de archivos" en el sentido de esta historia, ya cerrados en
  ENG-061/ENG-076.
- `DeliverWebhookJob`: su reintento a nivel de dominio con `$tries = 1` es una decisión ya
  cerrada de ENG-074, no se estandariza al patrón nativo de esta historia.
- Cualquier componente de aprendizaje automático, tendencias, riesgos o explicabilidad
  para "Analítica" — eso es ENG-080, Diferido.
- XLSX/PDF para exportaciones — siguen fuera de alcance (ya diferido explícitamente en
  ENG-062).

## Plan de verificación

Igual que en historias anteriores: Pest (Docker) por capa, Pint, PHPStan
(`--memory-limit=512M`) sobre los módulos tocados y luego sobre el repo completo antes del
cierre. Dado el tamaño, el trabajo se hace y se commitea en tramos (A+B, C, D, E, F) en vez
de un solo commit al final, manteniendo cada tramo verificado antes de continuar.
