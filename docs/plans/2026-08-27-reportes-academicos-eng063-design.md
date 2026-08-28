# ENG-063 — Reportes académicos: alcance acordado

Primera historia de la Fase 13 — Reportes y analítica. El roadmap lista seis reportes (Progreso, Rendimiento, Aprobación, Competencias, Actividad, Comparación por grupo) sin especificar cómo se calculan ni sobre qué se agrupan.

## Estado previo encontrado (investigación, no una decisión del usuario)

- `EnrollmentProgressRepository` solo tiene `findByEnrollmentId()` — ninguna consulta agregada por curso.
- `ExamAttemptRepository::all(?examId, ?userId, ?status)` ya soporta traer todos los intentos de un examen; `ExamRepository::all(?courseId)` ya soporta traer todos los exámenes de un curso — se pueden combinar (curso → exámenes → intentos) sin cambios de esquema.
- `CompetencyGrade` (desglose por competencia dentro de un `ExamAttempt`) ya existe, pero solo por intento individual — ninguna agregación entre intentos.
- **"Actividad" no tiene ningún dato base**: `User` no registra ninguna marca de tiempo de inicio de sesión ni concepto de actividad — habría que construirlo desde cero.
- **"Grupo" (cohorte/sección) no existe como concepto en el backend** (confirmado también en ENG-061) — no hay sobre qué agrupar para una "comparación por grupo" literal.
- Ninguna consulta de listado en `Modules\Academic` hace agregación SQL (`AVG`/`GROUP BY`) — todo el patrón existente (`PracticalResultCalculator`, `RoadPassportTrustCalculator`, `EnrollmentProgressCalculator`... ese último en realidad no persiste nada) es traer filas y calcular en PHP.
- `CourseLessonCatalog` (servicio de dominio ya existente) ya enumera los ids de lección de un curso — reutilizable para calcular el denominador de "porcentaje completado".

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance**: se construyen los seis reportes solicitados (el usuario rechazó explícitamente la propuesta de reducir a tres — única vez en toda la sesión que no se eligió la opción recomendada). Esto incluye construir "Actividad" desde cero.
2. **"Comparación por grupo"**: reinterpretado como "por curso". No se construye un endpoint de "comparar" separado; cada uno de los cinco reportes acepta una lista de `course_ids` — pedir el mismo reporte para dos o más cursos y ver los resultados lado a lado ES la comparación. Sin `course_ids`, el reporte cubre todos los cursos.
3. **Persistencia**: calculado al vuelo en cada consulta, sin tabla ni snapshot propio — mismo patrón que `PracticalResultCalculator`/`RoadPassportTrustCalculator`.
4. **Control de acceso**: se reutiliza el permiso `reports.view` ya creado en ENG-059 (SuperAdmin + InstitutionalAdmin), sin permiso nuevo.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Cinco reportes, no seis**: "Rendimiento" y "Aprobación" comparten exactamente la misma fuente de datos (los intentos de examen enviados de un curso) — se exponen como dos respuestas HTTP distintas (honrando que el usuario pidió los seis puntos del roadmap como conceptos separados), pero ambos handlers reutilizan un mismo servicio de aplicación (`CourseExamAttemptsLookup`) para no traer los intentos dos veces. "Comparación por grupo" no es un sexto reporte propio; es la capacidad de `course_ids` en los otros cinco.
- **`User::recordLogin()`**: nuevo método de dominio + campo `lastLoginAt` (nullable), poblado por `LoginUserUseCase` en cada inicio de sesión exitoso. Columna nueva vía migración separada (no se edita la migración original de `users`, mismo criterio que toda la sesión).
- **Dependencia entre módulos documentada**: el reporte de Actividad (`Modules\Academic`) depende de `Modules\Identity\Domain\Repositories\UserRepository::findById()` para leer `lastLoginAt` de cada usuario matriculado — mismo contrato público que `Modules\Authorization` ya usa para `AssignRoleHandler`, no una lectura directa de modelos Eloquent ajenos.
- **Sin filtros de fecha ni paginación**: cada reporte agrega el conjunto completo de datos disponibles hoy (todos los intentos enviados, todas las inscripciones), sin rango de fechas — igual que el resto del backend no pagina agregados todavía.
- **Umbral de actividad**: "activo" se define como `lastLoginAt` dentro de los últimos 30 días — valor fijo, no configurable en esta historia.

## Incluye (del roadmap)

- Progreso.
- Rendimiento.
- Aprobación.
- Competencias.
- Actividad.
- Comparación por grupo (vía `course_ids` en los cinco reportes anteriores).

## Diferido explícitamente

- Persistencia de reportes / recálculo programado.
- Filtros de fecha o paginación sobre los datos agregados.
- Un concepto real de "Grupo" (cohorte/sección) — la comparación es por curso.
- Umbral de actividad configurable.
- Reportes por organización (aunque `Enrollment`/`RoleAssignment` ya tienen `organizationId`, no se agrega esa dimensión en esta historia).
