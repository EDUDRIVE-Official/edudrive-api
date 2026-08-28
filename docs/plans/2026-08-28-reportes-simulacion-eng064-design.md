# ENG-064 — Reportes de simulación: alcance acordado

Segunda historia de la Fase 13 — Reportes y analítica. El roadmap lista seis reportes (Sesiones, Errores frecuentes, Infracciones, Evolución, Competencias prácticas, Riesgos detectados) sin especificar cómo se calculan ni sobre qué se agrupan — mismo patrón ambiguo que ENG-063.

## Estado previo encontrado (investigación, no una decisión del usuario)

- Ningún repositorio de `Modules\Simulation` filtra por nada hoy: `SimulationSessionRepository::all()`/`allForUser()` no aceptan parámetros; `TelemetryEventRepository`/`DecisionPointRepository` solo tienen `allForSession()`. Aún más plano que `Modules\Academic` antes de ENG-063.
- `TelemetryEventType` (enum cerrado: `Collision`/`Infraction`/`SignalUsage`/`Critical`) ya distingue "Infracción" como su propio caso — "Errores frecuentes" e "Infracciones" comparten exactamente la misma fuente de datos.
- `PracticalResultCalculator::calculate(SimulationSession, list<TelemetryEvent>)` y `DecisionEngineCalculator::calculate(sessionId, list<DecisionPoint>)` son servicios de dominio puros sin dependencias propias — ya calculan resultado/riesgo por sesión individual, reutilizables para agregar entre sesiones.
- `competenciesDemonstrated` (de `PracticalResultCalculator`) es hoy una sola cadena de texto libre por sesión (`"Conducción en escenario: {scenario}"` si aprobó, vacío si no) — sin ninguna estructura real que agregar, a diferencia de las competencias de ENG-063 (porcentaje real por competencia).
- `scenario` es texto libre sin catálogo; no hay un concepto de "curso" equivalente en Simulation. La única dimensión de agregación ya soportada por un método de repositorio es `userId` (vía `allForUser()`).

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance**: cuatro reportes — Sesiones, Errores e infracciones (unificados, contando frecuencia por cada caso de `TelemetryEventType`), Evolución (secuencia cronológica de resultados por sesión) y Riesgos detectados (agregado de `DecisionEngineCalculator` entre sesiones). Competencias prácticas diferido: no hay ninguna estructura real que agregar hoy.
2. **Dimensión de agregación**: por usuario (`userId`), reutilizando `allForUser()` ya existente. Por simulador queda diferido (requeriría un método de repositorio nuevo, hoy inexistente).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Sin módulo nuevo**: los cuatro reportes viven en `Modules\Simulation`, dueño de todos los datos fuente — mismo criterio que ENG-063.
- **Calculado al vuelo, sin persistencia**: mismo patrón ya establecido en ENG-063 (y ya usado internamente por `PracticalResultCalculator`/`DecisionEngineCalculator`).
- **Reutiliza el permiso `reports.view`** (ENG-059), sin permiso nuevo — mismo criterio que ENG-063.
- **`ReportUserIdsResolver`** (compartido por los cuatro, análogo a `ReportCourseResolver` de ENG-063): resuelve `user_ids` explícitos, o si viene vacío, descubre todos los `userId` distintos presentes en `SimulationSessionRepository::all()`. A diferencia de `ReportCourseResolver`, no valida que el usuario "exista" (no hay agregado `User` que consultar desde este módulo) — un `user_id` sin sesiones simplemente produce un reporte vacío para ese usuario, sin error.
- **Solo sesiones `Completed` alimentan Errores/Infracciones, Evolución y Riesgos**: una sesión programada o cancelada no tiene telemetría ni puntos de decisión que agregar; el reporte de Sesiones sí cuenta todos los estados (para reportar cuántas se completaron vs. cancelaron).
- **"Riesgos detectados" reporta las reacciones inapropiadas por nivel de riesgo** (no solo el conteo total de puntos de decisión): es la señal directamente accionable — cuántas veces el conductor reaccionó mal ante cada nivel de riesgo — además del conteo global apropiado/inapropiado y el promedio del `consistencyScore` ya calculado por sesión.

## Incluye (del roadmap)

- Sesiones.
- Errores frecuentes.
- Infracciones (unificado con Errores frecuentes).
- Evolución.
- Riesgos detectados.

## Diferido explícitamente

- Competencias prácticas (sin estructura real que agregar hoy).
- Agregación por simulador (requeriría un método de repositorio nuevo).
- Persistencia de reportes / recálculo programado.
- Filtros de fecha o paginación.
- Vínculo entre "competencias prácticas" y `Modules\Academic\...\Competency`.
