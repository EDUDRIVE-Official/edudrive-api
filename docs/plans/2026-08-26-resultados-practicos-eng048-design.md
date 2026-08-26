# ENG-048 — Resultados prácticos (Diseño)

## 1. Objetivo

Cerrar el ciclo de una sesión de simulación completada (`Completed`, ENG-046) con un resultado práctico derivado de su telemetría (ENG-047): resultado general, errores, penalizaciones, competencias demostradas, recomendaciones y evidencias asociadas.

## 2. Alcance acordado con el usuario

**Cálculo automático:** al consultarse, un servicio de dominio puro (`PracticalResultCalculator`, mismo espíritu que `RoadPassportTrustCalculator`/`ExamAttemptGrader`) cuenta los `TelemetryEvent` ya registrados de la sesión (`Collision`, `Infraction`, `Critical` — `SignalUsage` no penaliza, es informativo) y deriva un puntaje (100 menos penalización por evento: colisión -30, infracción -10, evento crítico -20, piso en 0) y un resultado general `Passed`/`Failed` (umbral 70, mismo criterio que `passingScore` en exámenes de Academic). Sin intervención humana.

**No se persiste:** el resultado se computa en cada consulta a partir de la telemetría ya persistida (que no cambia una vez la sesión está `Completed`), exactamente como el `trust_score` de `RoadPassportTrustCalculator` en ENG-042 — evita una tabla y una migración nuevas. Solo disponible cuando la sesión está `Completed` (`PRACTICAL_RESULT_NOT_AVAILABLE`, 422, si se consulta antes).

**Competencias demostradas:** lista de texto libre, sin depender del agregado `Competency` de Academic — solo se reporta una competencia (derivada del escenario de la sesión) cuando el resultado es `Passed`; una sesión reprobada no demuestra la competencia.

**Evidencias asociadas:** los propios `errors` del resultado son la evidencia — cada uno referencia el tipo de evento, la marca de tiempo y el detalle del `TelemetryEvent` concreto que lo originó. No hay un campo de "evidencia" separado ni integración con el Pasaporte Vial.

**Recomendaciones:** una recomendación fija por cada tipo de error presente en la sesión (colisión → practicar distancia de seguridad; infracción → repasar normas de tránsito; evento crítico → revisar manejo en situaciones de riesgo), sin duplicados.

**Diferido explícitamente:** registro manual de resultados por un evaluador humano; integración con el Pasaporte Vial (`RoadPassport::recordEvidence()`) — un resultado práctico aprobado alimentando evidencia del pasaporte queda fuera de alcance, mismo criterio que ENG-043 dejó fuera la emisión automática de certificados desde el pasaporte; referencias reales a `Competency` de Academic.

## 3. Módulo

Se extiende `Modules\Simulation` (mismo *bounded context* de ENG-045/046/047) — sin persistencia nueva.

## 4. Dominio

- `PracticalResultOutcome` (enum): `Passed`, `Failed`.
- `PracticalResultError` (VO): `type` (`TelemetryEventType`), `occurredAt`, `penaltyPoints`, `details` (nullable) — es la "evidencia" del error.
- `PracticalResultCalculator` (servicio de dominio puro): `calculate(SimulationSession, list<TelemetryEvent>): PracticalResult`.
- `PracticalResult` (VO): `sessionId`, `outcome`, `score` (0-100), `totalPenaltyPoints`, `errors` (list de `PracticalResultError`), `competenciesDemonstrated` (list de string), `recommendations` (list de string).

## 5. Aplicación (CQRS)

- `GetPracticalResultQuery(sessionId, userId, canViewOthers)` → `GetPracticalResultHandler`, mismo criterio de propiedad que `GetSimulationSessionHandler`/`GetSessionTelemetryHandler` (dueño o `simulation_sessions.view` — sin permiso nuevo). Carga la sesión y su telemetría, exige `status = Completed` (si no, `PracticalResultNotAvailable`, 422), invoca el calculador y devuelve `PracticalResultResponse`.

## 6. HTTP

`GET /api/v1/simulation/sessions/{sessionId}/result` bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view`. Error público nuevo: `PRACTICAL_RESULT_NOT_AVAILABLE` (422), además de `SIMULATION_SESSION_NOT_FOUND` (404, reutilizado).
