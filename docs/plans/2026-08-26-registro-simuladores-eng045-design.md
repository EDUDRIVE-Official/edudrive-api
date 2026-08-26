# ENG-045 — Registro de simuladores (Diseño)

## 1. Objetivo

Registro administrativo de simuladores SIMUDRIVE autorizados a integrarse con EDUDRIVE: quién es el dispositivo, qué versión de software corre, dónde está, su estado operativo, y una llave de integración para que el propio simulador se autentique contra la API en historias futuras (ENG-046 Sesiones de simulación, ENG-047 Telemetría).

## 2. Alcance acordado con el usuario

**Llaves de integración:** se genera una llave aleatoria al registrar (y al rotar); se devuelve **una única vez** en la respuesta HTTP de esa operación. En base de datos solo se guarda su hash (SHA-256), nunca el valor plano — mismo espíritu que los *personal access tokens* de Sanctum. Si se pierde, no hay forma de recuperarla: solo rotar (`POST /simulators/{id}/rotate-key`), lo que invalida la anterior.

**Ciclo de vida (bullet "Estado"):** `Active` (al registrar) → `Suspended` (reversible, ej. mantenimiento o incidente) → `Active` de nuevo (`reactivate`), o `Retired` (terminal, baja definitiva — no se reactiva, mismo criterio que `RoadPassport::revoke()`/`Certificate::revoke()`). Un simulador no `Active` no debería poder abrir sesiones en ENG-046 (esa validación se implementa ahí, no aquí).

**Diferido explícitamente:** validación de sesiones/telemetría contra el simulador (ENG-046/047 — este incremento solo registra el simulador, no lo usa todavía); actualización de la versión de software reportada por heartbeat del propio dispositivo (en este incremento la versión se fija al registrar, no hay canal de auto-reporte); geolocalización estructurada (`Ubicación` es texto libre, no coordenadas — no hay ningún caso de uso todavía que necesite calcular distancias o filtrar por región).

## 3. Módulo nuevo

`Modules\Simulation`, siguiendo ENG-003 al pie de la letra: capas Domain/Application/Infrastructure/Presentation, `SimulationServiceProvider`, endpoint de estado `GET /api/v1/simulation/status`, registrado en `bootstrap/providers.php`.

## 4. Dominio

- `SimulatorId` (VO, UUID) — mismo patrón que `RoadPassportId`/`CertificateId`.
- `DeviceIdentifier` (VO): identificador único del dispositivo físico (ej. número de serie), no vacío, máximo 100 caracteres — mismo espíritu que `CourseCode`.
- `IntegrationKey` (VO): `generate()` crea un valor aleatorio de 32 bytes (`random_bytes`, codificado en hexadecimal) y calcula su hash SHA-256; expone `plainValue()` (no nulo solo justo después de `generate()`) y `hash()`; `fromHash()` reconstruye desde persistencia (`plainValue()` siempre `null`); `matches(string $candidate)` compara con `hash_equals()` contra el hash almacenado (comparación segura contra *timing attacks*).
- `SimulatorStatus` (enum): `Active`, `Suspended`, `Retired`.
- `SimulatorHistoryEntry` (VO): `fromStatus`, `toStatus`, `occurredAt`, `reason` (nullable) — mismo patrón que `CertificateHistoryEntry`.
- `InvalidSimulatorTransition` (excepción de dominio, 422).
- Agregado `Simulator`: `id`, `deviceIdentifier`, `softwareVersion`, `location` (nullable), `status`, `integrationKey`, `registeredAt`, `history`.
  - `register(id, deviceIdentifier, softwareVersion, ?location, integrationKey, ?registeredAt)`: `status = Active`.
  - `suspend(?reason, at)`: solo desde `Active`.
  - `reactivate(at)`: solo desde `Suspended`.
  - `retire(?reason, at)`: desde `Active` o `Suspended`; rechaza si ya está `Retired`.
  - `rotateIntegrationKey(IntegrationKey $newKey)`: reemplaza la llave vigente (sin entrada de historial — no es un cambio de estado).
  - `restore(...)`: reconstrucción completa desde persistencia.

## 5. Persistencia

Tablas `simulators` (PK UUID, `device_identifier` único, `software_version`, `location` nullable, `status`, `integration_key_hash` único, `registered_at`) y `simulator_history_entries` (FK cascada a `simulators`). `EloquentSimulatorRepository::save()` transaccional, borra y reinserta el historial completo (mismo patrón que `EloquentCertificateRepository`/`EloquentRoadPassportRepository`).

## 6. Aplicación (CQRS)

- `RegisterSimulatorCommand(deviceIdentifier, softwareVersion, ?location)` → `RegisterSimulatorHandler`. Rechaza `deviceIdentifier` duplicado (`SimulatorAlreadyExists`, 409).
- `SuspendSimulatorCommand(simulatorId, ?reason)`, `ReactivateSimulatorCommand(simulatorId)`, `RetireSimulatorCommand(simulatorId, ?reason)` → sus handlers. `SimulatorNotFound` (404) si no existe.
- `RotateSimulatorIntegrationKeyCommand(simulatorId)` → `RotateSimulatorIntegrationKeyHandler`, genera una `IntegrationKey` nueva.
- `GetSimulatorQuery(simulatorId)` → `GetSimulatorHandler`.
- `ListSimulatorsQuery()` → `ListSimulatorsHandler`.
- `SimulatorResponse` (DTO): `id`, `device_identifier`, `software_version`, `location`, `status`, `registered_at`, `history` — **nunca** incluye el hash de la llave. Un campo opcional `integration_key` (valor plano) solo se agrega en la respuesta de `register`/`rotate-key`, nunca al consultar.

## 7. Autorización

Permisos nuevos `simulators.manage`/`simulators.view`, mismo patrón de concesión que `road_passports.*`/`certifications.*`: `SuperAdmin` e `InstitutionalAdmin` ambos; `Teacher` solo view (necesita saber qué simuladores existen para las sesiones de ENG-046); `Student` ninguno.

## 8. HTTP

Bajo `auth:sanctum`, prefijo `api/v1/simulation`:

- `GET /simulation/status` — sin autenticación (ENG-003 §18).
- `POST /simulation/simulators` (body: `device_identifier`, `software_version`, `location` opcional) — `simulators.manage`. Devuelve `integration_key` en texto plano.
- `GET /simulation/simulators` — `simulators.view`.
- `GET /simulation/simulators/{simulatorId}` — `simulators.view`.
- `POST /simulation/simulators/{simulatorId}/suspend` (body opcional `reason`) — `simulators.manage`.
- `POST /simulation/simulators/{simulatorId}/reactivate` — `simulators.manage`.
- `POST /simulation/simulators/{simulatorId}/retire` (body opcional `reason`) — `simulators.manage`.
- `POST /simulation/simulators/{simulatorId}/rotate-key` — `simulators.manage`. Devuelve el nuevo `integration_key` en texto plano.

Errores públicos: `SIMULATOR_NOT_FOUND` (404), `SIMULATOR_ALREADY_EXISTS` (409), `INVALID_SIMULATOR_TRANSITION` (422).
