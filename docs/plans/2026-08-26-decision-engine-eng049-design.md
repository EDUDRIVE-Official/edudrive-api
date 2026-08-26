# ENG-049 — SIMUDRIVE Decision Engine (Diseño)

## 1. Objetivo

Evaluar los puntos de decisión que un conductor enfrenta durante una sesión de simulación: qué situación vial se presentó, con qué nivel de riesgo, cómo reaccionó el conductor, si esa reacción fue apropiada, cuán consistente fue a lo largo de la sesión, y qué retroalimentación educativa corresponde.

## 2. Alcance acordado con el usuario

**Quién evalúa:** el simulador reporta datos crudos por punto de decisión — contexto vial (texto libre, mismo criterio que `vehicleType`/`scenario` en ENG-046: el catálogo real vive en SIMUDRIVE), nivel de riesgo de la situación (`Low`/`Medium`/`High`, asignado por el diseño del escenario en SIMUDRIVE, no algo que EDUDRIVE pueda inferir) y la reacción del conductor (una de un conjunto cerrado: `Braked`, `Accelerated`, `Maintained`, `Swerved`, `Signaled`, `Ignored` — necesario como conjunto cerrado, no texto libre, para que la evaluación sea determinística). Un servicio de dominio en EDUDRIVE (`DecisionEngineCalculator`, mismo espíritu que `PracticalResultCalculator` de ENG-048) evalúa si la reacción fue apropiada dado el riesgo, y genera retroalimentación — EDUDRIVE es quien decide, no solo ingiere.

**Regla de evaluación (determinística):** `Ignored` nunca es apropiado, sin importar el riesgo. Para riesgo `High`, solo `Braked`/`Swerved`/`Signaled` son apropiados. Para riesgo `Medium`, se suma `Maintained` a los apropiados. Para riesgo `Low`, cualquier reacción salvo `Ignored` es apropiada.

**Consistencia:** alcance limitado a la sesión actual (no al historial completo del usuario). Se agrupan los puntos de decisión por nivel de riesgo; un grupo es "consistente" si todas sus reacciones comparten el mismo resultado (todas apropiadas o todas inapropiadas) — el conductor responde de forma predecible ante situaciones de riesgo similar, no de forma errática. `consistency_score = grupos_consistentes / grupos_totales` (1.0 si solo hay un punto de decisión por grupo).

**Envío por lotes:** un único `POST` con un arreglo de puntos de decisión, autenticado con la llave de integración del simulador (`simulator.auth`, mismo mecanismo que la telemetría de ENG-047), exigiendo que la sesión exista, pertenezca a ese simulador y esté `InProgress` (reutiliza `SimulationSessionNotFound`/`SimulationSessionNotInProgress` de ENG-046/047).

**Sin persistencia del resultado evaluado:** igual que ENG-048, el resultado (evaluación + consistencia + retroalimentación) se calcula en cada consulta a partir de los puntos de decisión crudos ya persistidos (que no cambian una vez registrados) — solo se persisten los datos crudos (`DecisionPoint`, entidad de solo-append, mismo patrón que `TelemetryEvent`), no el resultado evaluado.

**Diferido explícitamente:** consistencia a través de múltiples sesiones o de todo el historial del usuario; que SIMUDRIVE reporte la evaluación ya calculada (delegar el criterio educativo a SIMUDRIVE); un catálogo cerrado de contextos viales (sigue siendo texto libre); retroalimentación personalizada más allá de mensajes fijos por combinación riesgo+resultado.

## 3. Módulo

Se extiende `Modules\Simulation` (mismo *bounded context* de ENG-045/046/047/048).

## 4. Dominio

- `DecisionRiskLevel` (enum): `Low`, `Medium`, `High`.
- `DriverReactionType` (enum): `Braked`, `Accelerated`, `Maintained`, `Swerved`, `Signaled`, `Ignored`.
- `DecisionEvaluationOutcome` (enum): `Appropriate`, `Inappropriate`.
- `DecisionPoint` (entidad inmutable, solo-append, mismo espíritu que `TelemetryEvent`): `id`, `sessionId`, `roadContext`, `riskLevel`, `driverReaction`, `occurredAt`.
- `DecisionPointEvaluation` (VO): `roadContext`, `riskLevel`, `driverReaction`, `outcome`, `feedback`, `occurredAt` — el resultado evaluado de un `DecisionPoint`.
- `DecisionEngineResult` (VO): `sessionId`, `evaluations` (list de `DecisionPointEvaluation`), `appropriateCount`, `inappropriateCount`, `consistencyScore` (0.0-1.0).
- `DecisionEngineCalculator` (servicio de dominio puro): `calculate(sessionId, list<DecisionPoint>): DecisionEngineResult` — aplica la regla de evaluación, agrupa por `riskLevel` para calcular consistencia, y genera retroalimentación fija por combinación riesgo+resultado.

## 5. Persistencia

Tabla `decision_points` (FK cascada a `simulation_sessions`, sin tabla de historial — *append-only*, igual que `telemetry_events`). `DecisionPointRepository::saveBatch()`/`allForSession()`.

## 6. Aplicación (CQRS)

- `SubmitDecisionPointsCommand(sessionId, simulatorId, decisions: list<array>)` → `SubmitDecisionPointsHandler`, mismo patrón de validación que `SubmitTelemetryHandler` (sesión existe, pertenece al simulador autenticado, está `InProgress`). Devuelve `DecisionPointsBatchResponse` (`decisions_recorded`).
- `GetDecisionEngineResultQuery(sessionId, userId, canViewOthers)` → `GetDecisionEngineResultHandler`, mismo criterio de propiedad que `GetPracticalResultHandler` (dueño o `simulation_sessions.view`, sin permiso nuevo); exige `status = Completed` (`DecisionEngineResultNotAvailable`, 422, nueva excepción, si se consulta antes).

## 7. HTTP

Bajo `api/v1/simulation`:

- `POST /sessions/{sessionId}/decisions` — middleware `simulator.auth` (sin `auth:sanctum`), body `decisions` (arreglo, cada uno validado por forma).
- `GET /sessions/{sessionId}/decisions` — bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view`.

Errores públicos: `SIMULATION_SESSION_NOT_FOUND`/`SIMULATION_SESSION_NOT_IN_PROGRESS` (reutilizados), `DECISION_ENGINE_RESULT_NOT_AVAILABLE` (422, nuevo).
