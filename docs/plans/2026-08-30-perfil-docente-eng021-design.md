# ENG-021 — Perfil del docente o instructor: diseño

**Fase:** 4 — Perfiles educativos.
**Alcance acordado:** texto libre nuevo para especialidades/certificaciones
(sin reutilizar `Modules\Certification`, concepto distinto); "permisos
de evaluación" solo se exponen, no se restringe el acceso a exámenes por
grupo asignado — ambas decisiones recomendadas y elegidas por el usuario.

## Contexto y hallazgos de la investigación

Un agente en background confirmó, viñeta por viñeta:

- **Especialidades**: terreno 100% nuevo (ningún precedente).
- **Certificaciones**: `Modules\Certification\Domain\Aggregates\Certificate`
  modela exclusivamente certificados de curso obtenidos por un
  **estudiante**, no credenciales profesionales de un docente — son
  conceptos distintos. El usuario decidió no reutilizarlo; es texto
  libre nuevo, igual que especialidades.
- **Organizaciones relacionadas**: resoluble por composición —
  `RoleAssignmentRepository::findByUserId()` ya devuelve todas las
  asignaciones del usuario con su `organizationId`; solo falta filtrar
  por `role === Teacher` y extraer las organizaciones (trivial, sin
  almacenamiento nuevo).
- **Grupos asignados**: `Group::teacherId` ya existe (ENG-019), pero
  `GroupRepository::all()` solo filtra por `courseId` — confirmado que
  el propio diseño de ENG-019 difirió explícitamente "filtrado de mis
  grupos para un docente". Requiere agregar un filtro opcional
  `teacherId` al repositorio existente (aditivo, no rompe los call
  sites actuales).
- **Permisos de evaluación**: el rol `Teacher` ya otorga `ViewExams`/
  `ViewExamAttempts` (solo lectura, sin restricción por grupo/curso
  asignado — cualquier docente con el permiso ve intentos de CUALQUIER
  estudiante del sistema). La calificación es 100% automática
  (`ExamAttemptGrader`), no existe ninguna acción manual de "calificar".
  El usuario decidió solo **exponer** en el perfil los permisos ya
  otorgados relacionados a exámenes/preguntas, sin construir ninguna
  restricción de acceso por grupo (eso reabriría una decisión ya tomada
  explícitamente en ENG-019) ni ninguna acción de calificación manual
  (no existe tal concepto en el diseño actual del sistema).

## Decisión de diseño

Mismo patrón que ENG-020 (`StudentProfile`), aplicado en espejo para el
docente.

### Domain (`modules/Identity/Domain/`)

- **`Entities/TeacherProfile.php`**: `userId`, `specialties` (`?string`,
  texto libre), `certifications` (`?string`, texto libre), `updatedAt`.
  `create()`/`restore()`/`update()`, mismo patrón que `StudentProfile`.
- **`Repositories/TeacherProfileRepository.php`**: `save()`,
  `findByUserId(string): ?TeacherProfile`.

### `GroupRepository::all()` gana un filtro opcional por docente

`all(?CourseId $courseId = null, ?string $teacherId = null): array` —
aditivo, no rompe los call sites existentes (todos usan argumentos
nombrados o un solo posicional). Implementado en
`EloquentGroupRepository` con un `where('teacher_id', ...)` adicional.

### Application (`modules/Identity/Application/`)

- **`UpdateTeacherProfileHandler`**: upsert, idéntico a
  `UpdateStudentProfileHandler`.
- **`GetMyTeacherProfileHandler`**: compone `UserRepository` (nombre),
  `TeacherProfileRepository` (especialidades/certificaciones),
  `RoleAssignmentRepository::findByUserId()` filtrado por
  `role === Teacher` (organizaciones relacionadas, deduplicadas),
  `GroupRepository::all(teacherId: ...)` (grupos asignados), y una
  lista fija de permisos relacionados a evaluación
  (`ViewExams`, `ManageExams`, `ViewExamAttempts`, `ViewQuestions`,
  `ManageQuestions`) verificados con `RolePermissions::grants()` contra
  cada `RoleAssignment` del usuario, deduplicados — mismo patrón de
  composición cross-módulo que `GetMyStudentProfileHandler`/
  `ExportMyDataUseCase`.

### Infrastructure

- Nueva tabla `teacher_profiles` (mismo esquema que `student_profiles`,
  columnas `specialties`/`certifications`).
- `TeacherProfileModel`/`TeacherProfileMapper`/
  `EloquentTeacherProfileRepository`, mismo patrón.

### Presentation

- **`GET/PUT /api/v1/auth/me/teacher-profile`**, dentro del grupo
  `auth:sanctum` ya existente, sin permiso adicional — siempre sobre el
  propio usuario autenticado (si no tiene rol Docente, organizaciones/
  grupos/permisos de evaluación simplemente vienen vacíos, igual que
  `StudentProfile` cuando el estudiante nunca lo completó).

## Fuera de alcance (documentado explícitamente)

- Reutilización de `Modules\Certification` para "certificaciones" del
  docente — concepto distinto, confirmado por el usuario.
- Restricción de acceso a intentos de examen por grupo/curso asignado
  al docente — reabriría la decisión de ENG-019 de no vincular
  `Enrollment`↔`Group`; fuera de alcance de una historia de perfil.
- Cualquier acción de calificación manual — no existe tal concepto en
  el diseño actual (calificación 100% automática).
- Endpoint para que un administrador consulte el perfil de OTRO
  docente — igual que ENG-020, exclusivamente `/me`.

## Plan de verificación

TDD por capa: Domain → Application → Infrastructure → Presentation. Pint
y PHPStan (`--memory-limit=512M`) tras cada capa y sobre el repo completo
al final. Suite de `Modules\Identity` y `Modules\Academic` (por el
cambio aditivo en `GroupRepository`) vía `./vendor/bin/pest`.
