# ENG-047 — Telemetría (Diseño)

## 1. Objetivo

Permitir que un simulador SIMUDRIVE reporte, durante una sesión `InProgress` (ENG-046), los datos de conducción que genera: velocidad, frenado, aceleración, dirección, uso de señales, colisiones, infracciones y eventos críticos — y que los usuarios autorizados puedan consultarlos después.

## 2. Alcance acordado con el usuario

**Quién reporta:** el simulador mismo, autenticado con su llave de integración (`Authorization: Bearer <llave>`, ENG-045) — no una sesión de usuario Sanctum. Primer mecanismo de autenticación máquina-a-máquina de este backend: un middleware nuevo (`Modules\Simulation\Presentation\Http\Middleware\AuthenticateSimulator`, alias `simulator.auth`) calcula el hash SHA-256 de la llave recibida y busca un simulador cuyo `integration_key_hash` coincida (mismo patrón que Laravel Sanctum para *personal access tokens*: comparación por hash indexado, no `hash_equals` byte a byte). Un simulador `suspended`/`retired` no puede autenticarse — revocar su acceso es tan simple como suspenderlo o retirarlo (ENG-045).

**Modelo de datos:** dos conceptos separados, sin invariantes de agregado (son bitácora de solo-append, no algo con ciclo de vida propio):
- `TelemetrySample` — lectura continua: velocidad, frenado, aceleración, dirección + marca de tiempo.
- `TelemetryEvent` — ocurrencia puntual: colisión, infracción, uso de señal o evento crítico, con tipo + detalle opcional + marca de tiempo.

**Modo de envío:** por lotes — un único `POST` con arreglos de lecturas y/o eventos acumulados por el simulador.

**Validaciones de negocio:** la sesión debe existir y pertenecer al simulador autenticado (si no, `SIMULATION_SESSION_NOT_FOUND`, 404 — mismo criterio anti-fuga que en ENG-046, aquí por pertenencia al simulador en vez de al usuario) y debe estar `InProgress` (si no, `SIMULATION_SESSION_NOT_IN_PROGRESS`, 422, excepción nueva) — no se puede reportar telemetría de una sesión que no ha iniciado, ya terminó o fue cancelada.

**Diferido explícitamente:** procesamiento o agregación de la telemetría (eso es ENG-048, Resultados prácticos); límites de tamaño de lote o *rate limiting* del endpoint (preocupación de infraestructura); reintentos/idempotencia ante envíos duplicados del mismo lote.

## 3. Módulo

Se extiende `Modules\Simulation` con dos entidades nuevas (no agregados — sin invariantes propios más allá de la validación de forma al construirse) y el middleware de autenticación de simuladores.

## 4. Dominio

- `TelemetryEventType` (enum): `Collision`, `Infraction`, `SignalUsage`, `Critical`.
- `TelemetrySample` (entidad inmutable): `id`, `sessionId`, `speedKph` (≥ 0), `brakingPercentage` (0-100), `accelerationMps2` (con signo, m/s²), `steeringAngleDegrees` (con signo), `recordedAt`. `record()` valida los rangos (defensivo — la validación primaria vive en el `FormRequest`, igual que `expires_at` en `IssueCertificateRequest`).
- `TelemetryEvent` (entidad inmutable): `id`, `sessionId`, `type`, `details` (nullable), `occurredAt`.
- No hay excepciones de dominio nuevas para estas entidades — errores de forma son `InvalidArgumentException`, igual que `DeviceIdentifier`/`ValidationCode`.

## 5. Persistencia

Tablas `telemetry_samples` y `telemetry_events` (ambas con `simulation_session_id` FK cascada a `simulation_sessions`, sin tabla de historial — no aplica el patrón borrar-y-reinsertar de los agregados, esto es *append-only*). `TelemetrySampleRepository::saveBatch(list<TelemetrySample>)`/`allForSession(string)` y el equivalente para `TelemetryEventRepository` — inserción masiva (`insert()` de Eloquent, no N `create()` individuales) para lotes grandes.

## 6. Autenticación de simuladores

`AuthenticateSimulator` (middleware, alias `simulator.auth`, registrado en `bootstrap/app.php` igual que `permission` → `EnsurePermission`): extrae el *bearer token*, calcula su hash y busca el simulador por `SimulatorRepository::findByIntegrationKeyHash()` (método nuevo en la interfaz existente). Si no hay token, no hay coincidencia, o el simulador no está `Active`, responde 401 (`UNAUTHENTICATED`/`INVALID_SIMULATOR_CREDENTIALS`). Si es válido, deja el id del simulador en `$request->attributes` para que el controlador lo use.

## 7. Aplicación (CQRS)

- `SubmitTelemetryCommand(sessionId, simulatorId, samples: list<array>, events: list<array>)` → `SubmitTelemetryHandler`. Valida pertenencia sesión↔simulador y estado `InProgress`, construye las entidades y las guarda en lote. Devuelve `TelemetryBatchResponse` (`samples_recorded`, `events_recorded`).
- `GetSessionTelemetryQuery(sessionId, userId, canViewOthers)` → `GetSessionTelemetryHandler`, mismo criterio de propiedad que `GetSimulationSessionHandler` (dueño de la sesión o `simulation_sessions.view` — no se crea ningún permiso nuevo). Devuelve `TelemetryResponse` (`session_id`, `samples`, `events`).

## 8. HTTP

Bajo `api/v1/simulation`:

- `POST /sessions/{sessionId}/telemetry` — middleware `simulator.auth` (sin `auth:sanctum`), body `samples`/`events` opcionales (arreglos, cada uno validado por forma).
- `GET /sessions/{sessionId}/telemetry` — bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view`.

Errores públicos: `SIMULATION_SESSION_NOT_FOUND` (404, reutilizado), `SIMULATION_SESSION_NOT_IN_PROGRESS` (422, nuevo).
