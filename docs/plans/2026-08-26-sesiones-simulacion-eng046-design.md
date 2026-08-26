# ENG-046 — Sesiones de simulación (Diseño)

## 1. Objetivo

Registrar cuándo un usuario usa un simulador SIMUDRIVE: qué simulador, qué vehículo, qué escenario, para cuándo se programó y cuánto duró — con un ciclo de vida que distinga "programada" de "en curso" de "finalizada"/"cancelada".

## 2. Alcance acordado con el usuario

**Ciclo de vida:** `Scheduled` (programada, fecha futura) → `InProgress` (inicio real, `start()`) → `Completed` (fin real, `complete()`, con duración efectiva calculada) — o `Cancelled` (`cancel()`, solo posible desde `Scheduled`; no se puede cancelar una sesión que ya inició). Este estado intermedio le da a ENG-047 (Telemetría) un punto explícito de "sesión activa ahora mismo" para asociar datos en tiempo real, y a ENG-048 (Resultados prácticos) el momento de cierre natural.

**Quién programa:** autoservicio — cualquier usuario autenticado programa su propia sesión (el `userId` se toma del usuario autenticado, nunca del cuerpo de la petición, para que nadie pueda programar a nombre de otro). Administradores/docentes ven y gestionan sesiones de terceros vía los permisos `simulation_sessions.manage`/`simulation_sessions.view`, extendiendo el mismo criterio de propiedad ya usado en `GetCertificateHandler`/`GetRoadPassportHandler` (dueño o permiso ampliado) también a las transiciones de estado (`start`/`complete`/`cancel`), no solo a la consulta — es la primera vez en este módulo que se aplica a mutaciones, pero es la extensión natural del mismo criterio ya establecido.

**Vehículo y escenario:** identificadores de texto libre (`vehicleType`, `scenario`), sin catálogo propio en EDUDRIVE — el catálogo real vive en SIMUDRIVE (sistema externo); EDUDRIVE solo registra qué valor se usó en cada sesión.

**Simulador debe estar activo:** al programar, se valida que el simulador exista (`SimulatorNotFound`, 404, reutilizando la excepción ya existente de ENG-045) y que su estado sea `Active` (`SimulatorNotAvailable`, 422) — un simulador suspendido o retirado no puede recibir sesiones nuevas.

**Diferido explícitamente:** detección de conflictos de horario entre sesiones del mismo simulador (dos sesiones programadas que se superpongan); re-validación del estado del simulador al iniciar la sesión (`start()`) — solo se valida al programar; integración real con telemetría del simulador (ENG-047) y resultados prácticos (ENG-048).

## 3. Módulo

Se extiende `Modules\Simulation` (mismo módulo de ENG-045) con un segundo agregado independiente `SimulationSession` — no hay necesidad de un módulo nuevo, ambos conceptos viven en el mismo *bounded context* de integración con SIMUDRIVE.

## 4. Dominio

- `SimulationSessionId` (VO, UUID).
- `SimulationSessionStatus` (enum): `Scheduled`, `InProgress`, `Completed`, `Cancelled`.
- `SimulationSessionHistoryEntry` (VO): `fromStatus`, `toStatus`, `occurredAt`, `reason` (nullable) — mismo patrón que `SimulatorHistoryEntry`/`CertificateHistoryEntry`.
- `InvalidSimulationSessionTransition` (excepción de dominio, 422).
- Agregado `SimulationSession`: `id`, `userId`, `simulatorId`, `vehicleType`, `scenario`, `scheduledAt`, `plannedDurationMinutes`, `status`, `startedAt` (nullable), `endedAt` (nullable), `history`.
  - `schedule(id, userId, simulatorId, vehicleType, scenario, scheduledAt, plannedDurationMinutes)`: `status = Scheduled`.
  - `start(at)`: solo desde `Scheduled`; fija `startedAt`.
  - `complete(at)`: solo desde `InProgress`; fija `endedAt`.
  - `cancel(?reason, at)`: solo desde `Scheduled`.
  - `actualDurationMinutes(): ?int`: `null` si no está `Completed`; si no, minutos entre `startedAt` y `endedAt`.
  - `restore(...)`: reconstrucción completa desde persistencia.

## 5. Persistencia

Tablas `simulation_sessions` (PK UUID, `user_id` FK a `users`, `simulator_id` FK a `simulators`, `vehicle_type`, `scenario`, `scheduled_at`, `planned_duration_minutes`, `status`, `started_at` nullable, `ended_at` nullable) y `simulation_session_history_entries` (FK cascada). `EloquentSimulationSessionRepository::save()` transaccional, borra y reinserta el historial completo (mismo patrón que `EloquentSimulatorRepository`).

## 6. Aplicación (CQRS)

- `ScheduleSimulationSessionCommand(userId, simulatorId, vehicleType, scenario, scheduledAt, plannedDurationMinutes)` → `ScheduleSimulationSessionHandler`. Valida que el simulador exista y esté `Active`.
- `StartSimulationSessionCommand(sessionId, userId, canManageOthers)`, `CompleteSimulationSessionCommand(sessionId, userId, canManageOthers)`, `CancelSimulationSessionCommand(sessionId, userId, canManageOthers, ?reason)` → sus handlers, mismo criterio de propiedad que las consultas.
- `GetSimulationSessionQuery(sessionId, userId, canViewOthers)` → `GetSimulationSessionHandler`.
- `GetMySimulationSessionsQuery(userId)` → `GetMySimulationSessionsHandler`.
- `ListSimulationSessionsQuery()` → `ListSimulationSessionsHandler` (administrativo, todas las sesiones).
- `SimulationSessionNotFound` (404) — se lanza tanto si no existe como si no es del usuario y no tiene permiso ampliado (mismo criterio anti-fuga que `CertificateNotFound`).
- `SimulatorNotAvailable` (422, nueva excepción) — el simulador existe pero no está `Active`.
- `SimulationSessionResponse` (DTO): `id`, `user_id`, `simulator_id`, `vehicle_type`, `scenario`, `scheduled_at`, `planned_duration_minutes`, `actual_duration_minutes`, `status`, `started_at`, `ended_at`, `history`.

## 7. Autorización

Permisos nuevos `simulation_sessions.manage`/`simulation_sessions.view`, mismo patrón de concesión que `road_passports.*`/`certifications.*`/`simulators.*`: `SuperAdmin` e `InstitutionalAdmin` ambos; `Teacher` solo view; `Student` ninguno (accede a las propias por pertenencia). Programar una sesión nueva no requiere ningún permiso — cualquier usuario autenticado programa la suya.

## 8. HTTP

Bajo `auth:sanctum`, prefijo `api/v1/simulation`:

- `POST /simulation/sessions` (body: `simulator_id`, `vehicle_type`, `scenario`, `scheduled_at`, `planned_duration_minutes`) — cualquier usuario autenticado, siempre para sí mismo.
- `GET /simulation/sessions/me` — cualquier usuario autenticado, lista las propias.
- `GET /simulation/sessions` — `simulation_sessions.view`, lista todas.
- `GET /simulation/sessions/{sessionId}` — dueño o `simulation_sessions.view`.
- `POST /simulation/sessions/{sessionId}/start` — dueño o `simulation_sessions.manage`.
- `POST /simulation/sessions/{sessionId}/complete` — dueño o `simulation_sessions.manage`.
- `POST /simulation/sessions/{sessionId}/cancel` (body opcional `reason`) — dueño o `simulation_sessions.manage`.

Errores públicos: `SIMULATION_SESSION_NOT_FOUND` (404), `SIMULATOR_NOT_AVAILABLE` (422), `INVALID_SIMULATION_SESSION_TRANSITION` (422), además de `SIMULATOR_NOT_FOUND` (404, reutilizado de ENG-045).
