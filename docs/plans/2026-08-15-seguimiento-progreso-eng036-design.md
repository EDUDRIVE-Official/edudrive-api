# ENG-036 - Seguimiento de progreso (Design)

## Goal

Registrar y consultar el avance de un estudiante en un curso en el que tiene una inscripción activa: lecciones completadas, tiempo invertido, evaluaciones realizadas, porcentaje de avance y última actividad.

## Context

`Modules\Academic` ya cuenta con:

- `Course` → `CourseModule` → `CourseUnit` (con `position`, `durationMinutes`, prerrequisitos a nivel módulo/unidad).
- `UnitContent`, agregado separado identificado por `CourseUnitId`, con `list<Lesson>` (cada `Lesson` tiene `LessonId`, `position`, `durationMinutes` propio y bloques de contenido).
- `Enrollment` (ENG-035): matrícula de un usuario en un curso, con estados `pending/active/completed/canceled`, sin ningún campo de progreso.
- `Exam`/`ExamAttempt`/`ExamAttemptGrader` (ENG-030-034): `Exam` referencia `courseId`; `ExamAttempt` referencia `examId` + `userId` (sin `enrollmentId` directo), con `submittedAt` y estado `submitted` cuando el intento se completó.

El diseño de ENG-035 dejó explícitamente "Progress" fuera de alcance (`docs/plans/2026-08-13-inscripciones-eng035-design.md`), a la espera de esta historia. No existe hoy ninguna tabla, agregado ni campo de progreso en el código.

Fases siguientes que consumirán este trabajo:

- **ENG-037** (Reglas de avance): usará el porcentaje/lecciones completadas para bloqueo/desbloqueo por prerrequisito.
- **ENG-038** (Learning Record Store): registrará eventos de aprendizaje más granulares; este incremento no lo reemplaza ni lo anticipa.

## Approaches Considered

### Option 1 - Agregado `EnrollmentProgress` con tabla de completitud por lección (recomendado)

Nuevo agregado 1:1 con `Enrollment`, respaldado por una tabla `academic_enrollment_lesson_completions` (una fila por lección completada). Porcentaje, tiempo invertido total y última actividad se calculan en la capa de aplicación combinando esas filas con el total de lecciones del curso y los intentos de examen del usuario para ese curso.

Ventajas: sigue el patrón ya validado en el proyecto (agregado dedicado + snapshot mínimo, como `ExamAttempt`); no duplica estado derivable; deja espacio limpio para tiempo por lección individual y para ENG-037/038.

Desventaja: una tabla y un query adicional para armar la respuesta de progreso.

### Option 2 - Campos de progreso embebidos en `Enrollment`

Agregar `completed_lesson_ids` (JSON), `time_spent_minutes` y `last_activity_at` directamente a `academic_enrollments`.

Ventajas: menos tablas y joins.

Desventajas: mezcla la responsabilidad de "matrícula" con la de "contenido consumido"; un array JSON que crece con cada lección es frágil ante escrituras concurrentes y no deja espacio limpio para tiempo por lección individual.

### Option 3 - Agregado `Progress` desacoplado de `Enrollment` (por `course_id` + `user_id`)

Sobrevive a cancelaciones o re-inscripciones.

Desventaja: indirección sin caso de uso real hoy — `Enrollment` ya modela unívocamente curso+usuario, y no existe necesidad de progreso fuera de una inscripción.

## Chosen Design

Se adopta la Opción 1.

## Domain Model

- **`EnrollmentProgress`** (nuevo agregado), identificado por `enrollmentId` (1:1 con `Enrollment`). Contiene `list<LessonCompletion>`.
- **`LessonCompletion`** (entidad, sin ID propio — clave natural `lessonId` único por enrollment): `lessonId`, `completedAt`, `timeSpentMinutes` (nullable).
- `EnrollmentProgress::completeLesson(lessonId, completedAt, timeSpentMinutes)`: upsert idempotente — si la lección ya estaba completada, actualiza `completedAt`/`timeSpentMinutes` en la fila existente en vez de duplicar.
- El porcentaje de avance, el tiempo total invertido y la última actividad **no se persisten**: se derivan en la capa de aplicación en el momento de la consulta.

## Persistence

Migración nueva: tabla `academic_enrollment_lesson_completions`.

- `id` (uuid, PK) — fila técnica.
- `enrollment_id` (uuid, FK a `academic_enrollments`, `cascadeOnDelete`, index).
- `lesson_id` (uuid, index, FK a `academic_lessons` con `cascadeOnDelete`) — nota: este diseño asumía originalmente que `Lesson` vivía embebida en `UnitContent` sin tabla propia; durante la implementación se confirmó que `academic_lessons` **sí** es una tabla real (`LessonModel`), por lo que se agregó la FK física en cascada. Efecto secundario documentado: si un docente elimina una lección del currículo de una unidad (acción ya existente de `courses.manage`), las filas de completitud de esa lección para todos los estudiantes se borran en cascada silenciosamente, perdiendo el historial de avance sin aviso. Se acepta como comportamiento por defecto para este incremento; queda pendiente de decisión de producto si se prefiere soft-delete o bloquear la eliminación de lecciones con completitudes registradas.
- `completed_at` (timestamp).
- `time_spent_minutes` (integer, nullable).
- `timestamps`.
- Índice único `(enrollment_id, lesson_id)` para garantizar idempotencia.

`EnrollmentLessonCompletionModel` (Eloquent) + `EloquentEnrollmentProgressRepository` implementando el contrato nuevo `EnrollmentProgressRepository` (`save`, `findByEnrollmentId`).

El total de lecciones del curso se obtiene reutilizando `CourseRepository`/`UnitContentRepository` ya existentes, sin tocar su esquema.

## Application Layer

- `CompleteLessonCommand(enrollmentId, lessonId, userId, timeSpentMinutes?)` → `CompleteLessonHandler`.
- `GetEnrollmentProgressQuery(enrollmentId, userId, canViewOthers)` → `GetEnrollmentProgressHandler`.

### `CompleteLessonHandler`

1. `Enrollment` debe existir y pertenecer al usuario autenticado; si no, `EnrollmentNotFound` (404) — sin distinguir "ajeno" de "inexistente", igual que el patrón ya usado en `ExamAttemptController`.
2. `Enrollment` debe estar `active`; si no, se reutiliza `InvalidEnrollment` (422).
3. La lección debe existir dentro del currículo del curso de la inscripción (recorrer `UnitContent` de las unidades del curso); si no, `LessonNotFound` (404, excepción nueva).
4. Se hace upsert en `EnrollmentProgress` y se persiste.
5. Devuelve la respuesta de progreso actualizada (mismo shape que el `GET`).

### `GetEnrollmentProgressHandler`

1. `Enrollment` debe existir; el usuario autenticado debe ser el dueño o tener el permiso `enrollments.view` ya existente (sin crear permiso nuevo); si no, `EnrollmentNotFound` (404).
2. Calcula:
   - `completed_lessons` (ids) y `completed_lessons_count`, desde `EnrollmentProgress`.
   - `total_lessons`, contando lecciones de todas las unidades del curso vía `UnitContentRepository`.
   - `progress_percentage` = `completed_lessons_count / total_lessons * 100` (0 si `total_lessons` es 0).
   - `time_spent_minutes`, suma de `timeSpentMinutes` no nulos en `EnrollmentProgress`.
   - `evaluations_completed`: se consulta `ExamAttemptRepository::all(userId: ..., status: Submitted)`, y de esos intentos se cuentan los cuyo `Exam` (vía `ExamRepository::findById`) tiene `courseId` igual al del enrollment.
   - `last_activity_at`: máximo entre la última `completedAt` de `EnrollmentProgress` y el último `submittedAt` de los intentos de examen filtrados en el punto anterior (`null` si no hay ninguna actividad).

## HTTP Surface

Base path: `/api/v1/academic`

- `POST /enrollments/{enrollmentId}/lessons/{lessonId}/complete`
  - Body: `{ "time_spent_minutes": 12 }` (opcional).
  - Respuesta: `200` con `data` = shape de progreso (ver abajo).
- `GET /enrollments/{enrollmentId}/progress`
  - Respuesta: `200` con `data` = shape de progreso.

Ambas rutas bajo `auth:sanctum`, sin middleware de permiso adicional — la autorización por pertenencia (dueño del enrollment) o por `enrollments.view` (terceros) se resuelve dentro del handler, igual que ya hace `ExamAttemptController::show`.

### Progress response shape

```json
{
  "data": {
    "enrollment_id": "uuid",
    "course_id": "uuid",
    "user_id": "uuid",
    "completed_lessons": ["lesson-uuid-1", "lesson-uuid-2"],
    "completed_lessons_count": 2,
    "total_lessons": 10,
    "progress_percentage": 20,
    "time_spent_minutes": 45,
    "evaluations_completed": 1,
    "last_activity_at": "2026-08-15T10:00:00+00:00"
  }
}
```

## Authorization Design

No se agregan permisos nuevos. Se reutiliza:

- Ninguno para completar la propia lección (basta `auth:sanctum` + verificación de dueño en el handler).
- `Permission::ViewEnrollments` para que terceros (SuperAdmin/InstitutionalAdmin/Teacher) consulten el progreso de otro usuario.

## Error Handling

Se reutiliza el pipeline de excepciones existente (`bootstrap/app.php`).

- `401` → no autenticado.
- `404` → `ENROLLMENT_NOT_FOUND` (inexistente o ajeno sin permiso), `LESSON_NOT_FOUND` (lección fuera del curso).
- `422` → `INVALID_ENROLLMENT` (enrollment no `active`), errores de validación de payload.

## Out of Scope

Este incremento no debe:

- Implementar reglas de bloqueo/desbloqueo por prerrequisito (ENG-037).
- Registrar eventos de aprendizaje granulares o Learning Record Store (ENG-038).
- Permitir "descompletar" una lección.
- Agregar progreso a nivel de curso/organización completa (solo por enrollment individual).
- Cambiar el esquema o comportamiento de `Exam`/`ExamAttempt`/`Enrollment` existentes.

## Testing Strategy

- **Dominio:** `EnrollmentProgressTest` — completar lección nueva, upsert idempotente sobre lección ya completada.
- **Aplicación:** `CompleteLessonHandlerTest`, `GetEnrollmentProgressHandlerTest` — enrollment inexistente/ajeno, enrollment no activo, lección fuera del curso, cálculo de porcentaje, cruce de evaluaciones con `ExamAttempt`, cálculo de última actividad.
- **Persistencia:** `EloquentEnrollmentProgressRepositoryTest` — roundtrip, upsert por índice único, cascada al borrar el enrollment.
- **Feature HTTP:** `EnrollmentProgressTest` — `401`, `404` (enrollment/lección), `422` (enrollment no activo), completar lección propia, consultar progreso propio, consultar progreso ajeno con y sin `enrollments.view`.

## Implementation Notes

- Mantener naming y estructura alineados con `ExamAttemptController`/`EnrollmentController` y sus rutas actuales.
- Usar `whereUuid()` en rutas con ids.
- Devolver siempre el envelope `{ "data": ... }` en respuestas exitosas.
- Reutilizar `CourseRepository`, `UnitContentRepository`, `ExamRepository` y `ExamAttemptRepository` ya existentes; no se modifican sus contratos.
