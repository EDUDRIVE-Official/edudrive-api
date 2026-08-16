# ENG-037 - Reglas de avance (Design)

## Goal

Bloquear y desbloquear módulos y unidades de un curso para un estudiante inscrito, según prerrequisitos ya modelados en `Course`, y exponer ese estado tanto en una consulta dedicada como al intentar completar una lección de una unidad todavía bloqueada.

## Context

`Modules\Academic` ya cuenta con:

- `Course` → `CourseModule` (con `prerequisiteModuleIds`) → `CourseUnit` (con `prerequisiteUnitIds`). `Course::validateCurriculum()` garantiza que un prerrequisito solo puede apuntar a un elemento **anterior** del mismo curso (nunca posterior ni al mismo), por lo que un ciclo es estructuralmente imposible. `prerequisiteUnitIds` se valida contra el acumulado global de unidades del curso, no por módulo, así que una unidad puede depender de una unidad de un módulo anterior.
- Hoy **ningún código lee estos campos** para bloquear avance; solo se serializan (`CourseSnapshotBuilder`) y se parsean al crear/actualizar el currículo (`CourseController`).
- `EnrollmentProgress` (ENG-036): expone `completedLessonIds()` a nivel de lección, sin ningún mapeo a unidad/módulo.
- `Exam`/`ExamAttempt` (ENG-030-034): `Exam` está anclado únicamente a `Course`, no a una unidad o módulo específico. `ExamAttempt::passed()` ya existe (comparación contra `passingScore`), pero no hay forma de vincular un examen a una unidad concreta sin una decisión de diseño propia y más grande.

Decisiones de alcance ya acordadas con el usuario:

- **Solo prerrequisitos por lecciones completadas.** El "puntaje mínimo" de examen queda fuera de este incremento — no se modifica `Exam` para añadirle `unitId`/`moduleId`.
- **Todas las lecciones son obligatorias.** No se agrega distinción obligatoria/opcional a `Lesson` (ENG-028, ya cerrado).
- **"Rutas adaptativas" se difiere por completo** — sin definición previa en el roadmap ni en `docs/plans/`; probablemente solapa con ENG-039 (Recomendaciones de aprendizaje), historia futura separada.
- **Enforcement en ambos lados:** una consulta de estado dedicada, y bloqueo real al intentar completar una lección de una unidad todavía bloqueada.

## Approaches Considered

### Option 1 - Servicio de dominio `CourseCurriculumUnlockCalculator` derivando todo en memoria (recomendado)

Un servicio de dominio nuevo recorre `Course` (módulos → unidades → lecciones vía `UnitContentRepository`, igual que `CourseLessonCatalog` pero conservando a qué unidad pertenece cada lección) y las lecciones completadas de un `EnrollmentProgress`, produciendo un value object `CurriculumUnlockStatus` con el estado completo (completado/desbloqueado) de cada módulo y unidad.

Ventajas: nada se persiste (se deriva siempre, como el resto de ENG-036); reutiliza `EnrollmentProgress`/`UnitContentRepository` ya existentes sin tocarlos; un único cálculo alimenta tanto la consulta de estado como el gate de `CompleteLessonHandler`.

Desventaja: recorre todo el currículo del curso en cada llamada (aceptable dado el tamaño típico de un curso; mismo patrón de costo que `CourseLessonCatalog`/`EnrollmentProgressCalculator`, ya aceptado en ENG-036).

### Option 2 - Persistir el estado de desbloqueo por enrollment

Guardar en una tabla `academic_enrollment_unit_unlocks` qué unidades están desbloqueadas, actualizándola cada vez que se completa una lección.

Desventaja: introduce un estado derivado duplicado que puede desincronizarse del currículo real (p. ej. si se edita el currículo después de que un estudiante ya avanzó), y complica la invalidación. Sin necesidad real hoy de esa optimización.

### Option 3 - Enforcement únicamente en el borde HTTP/controller

Calcular el desbloqueo en el controlador y devolver 403/422 antes de llamar al `CommandBus`.

Desventaja: rompe la separación ya establecida en el módulo (autorización/reglas de negocio viven en application/domain, no en el controller); duplicaría la lógica si en el futuro se necesita el mismo gate desde otro punto de entrada (CLI, cola, etc.).

## Chosen Design

Se adopta la Opción 1.

## Domain Model

- **`CourseCurriculumUnlockCalculator`** (nuevo servicio de dominio, constructor con `UnitContentRepository`, mismo patrón que `CourseLessonCatalog`).
  - `statusFor(Course $course, EnrollmentProgress $progress): CurriculumUnlockStatus`.
- **`CurriculumUnlockStatus`** (nuevo value object):
  - Por unidad: `completed` (todas sus lecciones están en `EnrollmentProgress`; una unidad sin lecciones publicadas cuenta como completada, igual que `CourseLessonCatalog` la trata como vacía) y `unlocked`.
  - Por módulo: `completed` (todas sus unidades completadas) y `unlocked`.
  - Reglas de desbloqueo:
    - Un módulo está desbloqueado si todos los módulos en su `prerequisiteModuleIds` están completados (sin prerrequisitos → siempre desbloqueado).
    - Una unidad está desbloqueada si su módulo padre está desbloqueado **y** todas las unidades en su `prerequisiteUnitIds` están completadas (pueden pertenecer a un módulo anterior).
  - `unitIdForLesson(LessonId $lessonId): ?CourseUnitId` — resuelve a qué unidad pertenece una lección, para que `CompleteLessonHandler` pueda verificar el desbloqueo sin que el cliente tenga que enviar el `unitId`.
  - `isUnitUnlocked(CourseUnitId $unitId): bool`.

## Application Layer

- **Nueva excepción** `UnitLocked` (422, `UNIT_LOCKED`).
- **`CompleteLessonHandler` (ENG-036, ya existente) se extiende**: después de confirmar que la lección pertenece al curso (paso ya existente, `LessonNotFound`), calcula `CurriculumUnlockStatus` y verifica que la unidad de esa lección esté desbloqueada; si no, lanza `UnitLocked`. El resto del handler no cambia.
- **`GetEnrollmentCurriculumStatusQuery(enrollmentId, userId, canViewOthers)`** → **`GetEnrollmentCurriculumStatusHandler`**: misma regla de autorización que `GetEnrollmentProgressHandler` (dueño del enrollment, o `Permission::ViewEnrollments` para terceros — sin permiso nuevo).
- **`CurriculumUnlockResponse`**: lista de módulos (`module_id`, `completed`, `unlocked`) con su lista anidada de unidades (`unit_id`, `completed`, `unlocked`).

## HTTP Surface

- `GET /api/v1/academic/enrollments/{enrollmentId}/curriculum` — bajo `auth:sanctum`, sin middleware de permiso adicional (autorización por pertenencia en el handler).
- `POST /api/v1/academic/enrollments/{enrollmentId}/lessons/{lessonId}/complete` (ENG-036, ya existente) ahora puede responder `422 UNIT_LOCKED` además de sus errores actuales.

### Curriculum status response shape

```json
{
  "data": {
    "enrollment_id": "uuid",
    "course_id": "uuid",
    "modules": [
      {
        "module_id": "uuid",
        "completed": true,
        "unlocked": true,
        "units": [
          { "unit_id": "uuid", "completed": true, "unlocked": true },
          { "unit_id": "uuid", "completed": false, "unlocked": true }
        ]
      },
      {
        "module_id": "uuid",
        "completed": false,
        "unlocked": false,
        "units": [
          { "unit_id": "uuid", "completed": false, "unlocked": false }
        ]
      }
    ]
  }
}
```

## Authorization Design

No se agregan permisos nuevos. Se reutiliza `Permission::ViewEnrollments` para que terceros con acceso ampliado consulten el estado de currículo de otro usuario, igual que en `GetEnrollmentProgressHandler`.

## Error Handling

- `401` → no autenticado.
- `404` → `ENROLLMENT_NOT_FOUND` (reutilizado), `LESSON_NOT_FOUND` (reutilizado, ENG-036).
- `422` → `INVALID_ENROLLMENT` (reutilizado, enrollment no `active`), `UNIT_LOCKED` (nuevo).

## Out of Scope

- Gating por "puntaje mínimo" de examen (requiere anclar `Exam` a una unidad/módulo — decisión de diseño propia y más grande).
- Distinción lección obligatoria/opcional en `Lesson`.
- Rutas adaptativas.
- Persistencia del estado de desbloqueo (siempre derivado, como el resto de ENG-036).

## Testing Strategy

- **Dominio:** `CourseCurriculumUnlockCalculatorTest` — módulo/unidad sin prerrequisitos siempre desbloqueados; unidad bloqueada por prerrequisito de unidad no completado; módulo bloqueado por prerrequisito de módulo no completado; prerrequisito de unidad que cruza módulos; unidad sin lecciones publicadas cuenta como completada; `unitIdForLesson` resuelve correctamente.
- **Aplicación:** extender `CompleteLessonHandlerTest` con el caso de unidad bloqueada (`UnitLocked`); nuevo `GetEnrollmentCurriculumStatusHandlerTest` (dueño, ajeno sin permiso, ajeno con permiso, enrollment inexistente).
- **Feature HTTP:** nuevo `EnrollmentCurriculumTest` (consulta propia, ajena sin/con `enrollments.view`, enrollment inexistente) y un caso adicional en `EnrollmentProgressTest` (o el nuevo archivo) para `422 UNIT_LOCKED` al completar una lección de una unidad bloqueada.

## Implementation Notes

- Mantener naming y estructura alineados con `CourseLessonCatalog`/`EnrollmentProgressCalculator` y `EnrollmentProgressController`.
- Usar `whereUuid()` en la ruta nueva.
- Devolver siempre el envelope `{ "data": ... }`.
- Reutilizar `CourseRepository`, `UnitContentRepository`, `EnrollmentRepository`, `EnrollmentProgressRepository` ya existentes; no se modifican sus contratos.
