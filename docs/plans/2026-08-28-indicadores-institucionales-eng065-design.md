# ENG-065 — Indicadores institucionales: alcance acordado

Tercera y última historia de la Fase 13 — Reportes y analítica. El roadmap lista seis indicadores (Participación, Finalización, Desempeño, Impacto, Adopción, Uso por sede) sin especificar cómo se calculan ni sobre qué se agrupan — mismo patrón ambiguo que ENG-063/064.

## Estado previo encontrado (investigación, no una decisión del usuario)

- `Organization` tiene `Campus` como entidad hija con id propio, pero **nada la referencia**: ni `Enrollment` ni `RoleAssignment` tienen `campusId`, solo `organizationId`. "Uso por sede" no tiene ningún dato hoy.
- `Certificate` (Certification), `RoadPassport` y los agregados de Gamification (`Achievement`/`Badge`/`UserAchievement`/`UserBadge`/`ExperienceEntry`) **no tienen ningún campo de organización** — "Impacto" no tiene ningún vínculo organizacional real en ninguna fuente candidata.
- `EnrollmentRepository::all(?CourseId, ?string $userId, ?string $organizationId, ?EnrollmentStatus, ?EnrollmentSource)` **ya filtra por `organizationId`** a nivel SQL — suficiente para recortar inscripciones por organización sin cambios de esquema.
- `EnrollmentStatus` tiene `Completed` como estado terminal real, distinto de `Active`/`Canceled`.
- `ExamAttemptRepository`/`ExamRepository` no tienen ningún concepto de organización (los exámenes pertenecen a cursos, no a organizaciones) — un reporte de desempeño por organización requiere cruzar: inscripciones de la organización → cursos → exámenes de esos cursos → intentos, filtrando a los usuarios inscritos institucionalmente en esa organización.
- Ningún repositorio soporta filtro por rango de fechas; `Enrollment.enrolledAt` es el único campo de fecha ya disponible para construir una serie temporal (mismo patrón fetch-all-then-compute que ENG-063/064).

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance**: cuatro indicadores — Participación, Finalización, Desempeño y Adopción — todos agregados por organización, reutilizando `Enrollment.organizationId` ya soportado. Impacto se difiere (ninguna fuente candidata tiene vínculo organizacional real). Uso por sede se difiere (Campus no tiene ningún dato que agregar hoy; agregarlo requeriría una migración y cambio de dominio en Academic/Authorization, no solo un reporte).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Viven en `Modules\Academic`**, no en `Modules\Organization`: tres de los cuatro indicadores son mayoritariamente datos de Academic (Enrollment/EnrollmentProgress/ExamAttempt); Organization solo aporta la lista de organizaciones sobre la cual iterar — mismo criterio que el reporte de Actividad de ENG-063, que vivió en Academic aunque dependiera de `Identity\UserRepository` para resolver `lastLoginAt`.
- **`ReportOrganizationResolver`** (análogo a `ReportCourseResolver`/`ReportUserIdsResolver`): resuelve `organization_ids` explícitos (validando existencia vía `OrganizationRepository::findById()`, lanzando `Organization\Application\Exceptions\OrganizationNotFound` si no existe — reutilizada tal cual desde `Modules\Organization`, mismo criterio que reutilizar cualquier `DomainException` ajena, el manejador de excepciones global ya la renderiza) o, si la lista viene vacía, todas las organizaciones vía `OrganizationRepository::all()`.
- **Desempeño reutiliza `CourseExamAttemptsLookup`** (ya construido en ENG-063): para cada curso con inscripciones institucionales en la organización, se piden los intentos enviados del curso y se filtran a solo los usuarios inscritos institucionalmente en esa organización para ese curso — evita contar intentos de estudiantes de otras organizaciones o autoinscritos individualmente en el mismo curso.
- **Participación**: mide inscripciones con al menos una lección completada (`EnrollmentProgress::completedLessonIds() !== []`), no solo inscripciones activas — una inscripción sin ninguna actividad no cuenta como "participación" real.
- **Adopción**: conteo de inscripciones nuevas por mes (`enrolledAt` agrupado por año-mes), en orden cronológico — la primera serie temporal de toda la Fase 13, construida con el mismo patrón fetch-all-then-compute ya usado en todos los cálculos derivados de la sesión.
- **Calculado al vuelo, sin persistencia**; reutiliza el permiso `reports.view` — mismos criterios ya establecidos en ENG-063/064.

## Incluye (del roadmap)

- Participación.
- Finalización.
- Desempeño.
- Adopción.

## Diferido explícitamente

- Impacto (ninguna fuente candidata tiene vínculo organizacional real).
- Uso por sede (Campus no tiene ningún dato que agregar; requeriría agregar `campusId` a `Enrollment`/`RoleAssignment`).
- Persistencia de indicadores / recálculo programado.
- Filtros de fecha configurables sobre Adopción (el agrupamiento mensual es fijo).
