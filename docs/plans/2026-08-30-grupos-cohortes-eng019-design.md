# ENG-019 — Grupos y cohortes: diseño

**Fase:** 3 — Organizaciones e instituciones.
**Alcance acordado:** un único aggregate nuevo `Group` que unifica
Secciones + Asignación de docentes + Periodos lectivos; Cohorte/
Generación se tratan como el nombre del grupo, no como conceptos
separados; `Enrollment` no se toca (`Group` queda como catálogo
standalone) — ambas decisiones recomendadas y elegidas por el usuario.

## Contexto y hallazgos de la investigación

Un agente en background confirmó que ENG-019 es terreno **100% nuevo**:
no existe ningún aggregate, columna, enum ni relación reutilizable para
sección/grupo/cohorte/generación/periodo lectivo en ningún módulo.
`Enrollment` vincula directamente usuario+curso sin ningún nivel
intermedio; `Course` no tiene `teacherId` ni fechas de dictado (solo
fechas de ciclo de vida editorial: `publishedAt`/`archivedAt`); la única
relación de un docente con algo es `RoleAssignment(role: Teacher)`, sin
vínculo a un curso o grupo específico.

## Decisión de diseño

### Domain (`modules/Academic/Domain/`)

- **`Aggregates/Group.php`**: `id` (`GroupId`), `courseId` (`CourseId`),
  `organizationId` (`?string`), `name` (string, no vacío — aquí vive
  "Cohorte"/"Generación" como convención de nombre, ej. "Generación
  2026-I"), `teacherId` (`?string`, nullable — se asigna al crear o
  después), `startsAt`/`endsAt` (`DateTimeImmutable`, periodo lectivo).
  Invariante: `endsAt` > `startsAt`. `create()`/`restore()`.
  `assignTeacher(?string $teacherId)` para reasignar el docente después
  de creado el grupo.
- **`ValueObjects/GroupId.php`**: mismo patrón UUID que `CourseId`.
- **`Exceptions/InvalidGroupPeriod.php`**: `DomainException` (422) —
  `endsAt` no posterior a `startsAt`.

No se valida la existencia del `teacherId` contra `Modules\Identity` ni
su rol contra `Modules\Authorization` — confirmado que ningún otro
aggregate de `Academic` (incluido `Enrollment`, que ya referencia
`userId`) hace ese cruce; se mantiene la misma convención.

### Application (`modules/Academic/Application/`)

- **`CreateGroupCommand`**/**`CreateGroupHandler`**: valida que
  `courseId` exista (`CourseRepository::findById`, si no
  `CourseNotFound`, ya existente); crea el `Group` y lo guarda.
- **`AssignGroupTeacherCommand`**/**`AssignGroupTeacherHandler`**: busca
  el grupo (`GroupNotFound` si no existe, nueva excepción 404), llama
  `assignTeacher()`, guarda.
- **`GroupResponse`**: DTO de salida (`toArray()`), mismo patrón que
  `CompetencyResponse`.
- Consulta: **`ListGroupsQuery`**/**`ListGroupsHandler`**, con filtro
  opcional `courseId`.

### Infrastructure (`modules/Academic/Infrastructure/`)

- Nueva migración `create_academic_groups_table`: `id` (uuid, PK),
  `course_id` (uuid, FK real a `academic_courses`, `cascadeOnDelete` —
  mismo patrón que `academic_enrollments`), `organization_id` (uuid,
  nullable, **sin** FK — mismo patrón que `academic_enrollments.organization_id`,
  evita acoplar la tabla a `Modules\Organization`), `name` (string 150),
  `teacher_id` (uuid, nullable, FK a `users`, `nullOnDelete`),
  `starts_at`/`ends_at` (timestamp), timestamps.
- `GroupModel`/`EloquentGroupRepository` (`Infrastructure/Persistence/Eloquent/{Models,Repositories}`),
  mapeo inline en el repositorio — mismo patrón que
  `EloquentCompetencyRepository` (Academic no usa una clase Mapper
  separada, a diferencia de Identity).

### Presentation (`modules/Academic/Presentation/Http/`)

- **`POST /api/v1/academic/groups`** (`permission:groups.manage`):
  crea un grupo.
- **`GET /api/v1/academic/groups`** (`permission:groups.view`, filtro
  opcional `course_id` en query string): lista grupos.
- **`POST /api/v1/academic/groups/{groupId}/assign-teacher`**
  (`permission:groups.manage`): reasigna el docente de un grupo.
- Nuevos permisos `Permission::ManageGroups` (`groups.manage`)/
  `Permission::ViewGroups` (`groups.view`) en `Modules\Authorization`:
  `SuperAdmin`/`InstitutionalAdmin` con ambos; `Teacher` solo con
  `ViewGroups` (puede consultar el catálogo de grupos, sin filtrarse
  todavía a "los suyos" — no hay vínculo a `Enrollment` ni a
  `RoleAssignment` en esta primera versión); `Student` sin acceso.

## Fuera de alcance (documentado explícitamente)

- Cualquier vínculo `Enrollment` → `Group` (campo `groupId`) — el
  catálogo de grupos queda standalone esta vez; la vinculación real
  estudiante-grupo es candidata a una historia futura cuando haya una
  necesidad concreta.
- Cohorte/Generación como aggregate propio que agrupe varios `Group`
  bajo un mismo periodo de ingreso, sin importar el curso.
- Validación cruzada de `teacherId` contra `Identity`/`Authorization`
  (existencia del usuario, verificación de que tenga rol Docente).
- Filtrado de "mis grupos" para un docente autenticado (requeriría el
  vínculo `RoleAssignment`↔`Group` explícitamente fuera de alcance).

## Plan de verificación

TDD por capa: Domain → Application → Infrastructure → Presentation. Pint
y PHPStan (`--memory-limit=512M`) tras cada capa y sobre el repo completo
al final. Suite completa de `Modules\Academic` y `Modules\Authorization`
vía `./vendor/bin/pest`.
