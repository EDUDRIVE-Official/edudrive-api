# ENG-083 — Observabilidad: diseño

**Fase:** 17 — Plataforma y operación avanzada
**Alcance acordado:** reducido (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

El roadmap solo trae seis viñetas sueltas sin documento de diseño: Logs
estructurados, Métricas, Trazas, Correlation ID, Alertas, Dashboards. La
investigación (un agente en background) encontró:

- **Correlation ID ya existe** (`Modules\Foundation\Presentation\Http\Middleware\CorrelationId`,
  header `X-Correlation-ID`, reutiliza o genera un UUID, lo guarda vía
  `Context::add('correlation_id', ...)`), pero **solo lo consume explícitamente
  `Modules\Audit`**. No aparece en el payload de error que recibe el cliente,
  ni se propaga a los Jobs en cola (que corren en un proceso de worker
  separado, sin el `Context` de la request original).
- **Logs en texto plano**: ningún canal de `config/logging.php` usa
  `Monolog\Formatter\JsonFormatter`.
- **9 `Log::warning(...)` existentes** (todos en métodos `failed()` de Jobs de
  ENG-081/082) con contexto modesto, sin `correlation_id`.
- **Excepciones no manejadas** se reportan con `report($exception)` (el
  reporter default de Laravel) sin ningún contexto adicional enriquecido por
  la aplicación (sin correlation_id, ruta, método o usuario).
- **Canal `slack` boilerplate** ya existe en `config/logging.php` pero no
  está en el `stack` por defecto y **tiene un bug pre-existente**: su
  `'level' => env('LOG_LEVEL', 'critical')` hereda la variable de entorno
  genérica `LOG_LEVEL` (usada para `single`/`daily`) en vez de tener su
  propio nivel mínimo dedicado — si `LOG_LEVEL=debug` (como en este repo),
  el canal Slack heredaría `debug` y saturaría el canal con absolutamente
  todo en cuanto se active, no solo errores críticos.
- **Métricas, trazas distribuidas y dashboards son inexistentes** y cada una
  requeriría infraestructura externa nueva (Prometheus + endpoint `/metrics`,
  OpenTelemetry + collector, Grafana) — fuera de este alcance reducido.

## Decisiones de diseño

### A. Logs estructurados (JSON)

Los canales `single` y `daily` de `config/logging.php` (los que realmente
escriben a disco en este proyecto) ganan `'formatter' => JsonFormatter::class`.
Los demás canales (`slack`, `stderr`, `syslog`, `papertrail`, `errorlog`) no
se tocan — cada uno ya tiene su formato natural apropiado para su destino.

### B. Correlation ID reforzado

- **Payload de error**: `ApiErrorResponse::make()` y `::unexpected()` ganan
  `'correlation_id' => Context::get('correlation_id')` en el JSON de
  respuesta — el cliente puede reportar ese ID al soporte técnico.
- **Jobs en cola**: los Jobs corren en un proceso de worker separado, así
  que el `Context` de la request HTTP original no se propaga automáticamente.
  Los 9 Jobs con `Log::warning(...)` en su método `failed()` (`ExportAuditLogsJob`,
  `ExportCoursesJob`, `ExportEnrollmentsJob`, `ImportCoursesJob`,
  `ImportQuestionsJob`, `ImportUsersJob`, `GenerateAnalyticsReportJob`,
  `SendMobilePushJob`, `SendEmailNotificationJob`) ganan una propiedad
  `public readonly ?string $correlationId`, capturada en el constructor vía
  `Context::get('correlation_id')` — el constructor se ejecuta en el proceso
  HTTP original al momento de `dispatch()`, así que captura el valor
  correcto antes de que el Job se serialice y viaje al worker. Se agrega al
  array de contexto de cada `Log::warning(...)` existente.
- **Excepciones no manejadas**: `bootstrap/app.php` gana
  `$exceptions->context(fn (): array => [...])` (mecanismo nativo de Laravel
  11+ para adjuntar contexto global a cada excepción reportada) con
  `correlation_id`, `url`, `method` y `user_id` de la request en curso —
  sin tener que enriquecer cada `render(...)` individual ya existente.

### C. Alertas (canal Slack real)

- El canal `stack` incluye `slack` automáticamente solo cuando
  `LOG_SLACK_WEBHOOK_URL` está configurado (`array_filter` sobre la lista de
  canales) — no rompe entornos sin webhook configurado (desarrollo local).
- Se corrige el bug de nivel encontrado: el canal `slack` gana su propia
  variable `LOG_SLACK_LEVEL` (default `critical`), independiente de
  `LOG_LEVEL` genérico — así una alerta a Slack solo se dispara para errores
  verdaderamente críticos, sin importar el nivel de detalle configurado para
  los logs en disco.

## Fuera de alcance (documentado explícitamente)

- Endpoint `/metrics` en formato Prometheus y cualquier librería de métricas
  — requeriría una dependencia nueva y una decisión de qué medir, sin
  infraestructura de scraping (Prometheus) provisionada.
- Trazas distribuidas (OpenTelemetry/Jaeger/Zipkin, `trace_id`/`span_id`) —
  requeriría un SDK y un collector nuevos.
- Dashboards (Grafana, Telescope, Horizon) — requeriría contenedores nuevos
  en `compose.yaml` y no hay una fuente de métricas/trazas aún que mostrar.

## Plan de verificación

Mismo ritmo establecido: Pest (Docker), Pint, PHPStan (`--memory-limit=512M`)
sobre los módulos tocados y luego sobre el repo completo antes del cierre.
