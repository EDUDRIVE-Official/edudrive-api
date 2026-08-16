# ENG-038 - Learning Record Store interno (Design)

## Goal

Registrar como hechos inmutables (append-only) los eventos de aprendizaje que ya ocurren hoy en `Academic` — lección completada y examen enviado — y exponer una consulta dedicada por inscripción, sentando la base de un Learning Record Store interno.

## Context

`Modules\Academic` ya tiene los puntos de origen de estos eventos:

- `CompleteLessonHandler` (ENG-036) completa una lección de un `EnrollmentProgress`.
- `SubmitExamAttemptHandler` (ENG-032/033) envía y califica un `ExamAttempt` (anclado a `Course` vía `courseId`, no a `Enrollment` directamente; `EnrollmentRepository::findActiveOrPendingFor(CourseId, userId)`, ya existente desde ENG-035, resuelve el enrollment del alumno para ese curso).

No existe infraestructura de domain events/event bus en el proyecto. El precedente más cercano es el módulo `Audit` (log de auditoría simple: `AuditEntry`/`AuditLogger`/`AuditLogModel`, usado hoy solo para `auth.login`/`auth.logout`/`auth.logout_all`, invocado directamente desde los use cases de `Identity` tras la acción principal — sin event bus).

Decisiones de alcance ya acordadas con el usuario:

- **Solo instrumentar handlers existentes.** Nada de endpoint de ingesta genérico: móvil usa la misma API web; simulador (ENG-045/046) no existe todavía, así que queda fuera de alcance real aunque el modelo de datos no impide agregarlo después.
- **Solo dos eventos en este incremento**: lección completada (ENG-036) e intento de examen enviado (ENG-032/033). Inscripción y autenticación quedan fuera.
- **Lectura con la misma autorización que ENG-036/037**: dueño del enrollment o permiso ya existente `enrollments.view`, sin permiso nuevo.
- **Módulo nuevo `Modules\Learning`**, no una extensión de `Audit` ni algo embebido en `Academic`: es un concepto transversal que crecerá (ENG-039 recomendaciones, futuros reportes).

## Approaches Considered

### Option 1 - DDD completo, igual que `Academic` (recomendado)

`LearningEvent` como entidad de dominio inmutable (análoga a `LessonCompletion`, ya usada dentro de `EnrollmentProgress`), con sus propios value objects, una interfaz `LearningEventRepository` en el dominio y su implementación Eloquent en infraestructura.

Ventaja: consistente con el patrón que sigue el resto del código (`Academic`, `Identity`, `Organization`); dado que `Learning` va a crecer (ENG-039 y reportes futuros), tener la estructura de dominio lista evita una migración de arquitectura más adelante.

Desventaja: más código para este primer incremento que un log plano.

### Option 2 - Estilo `Audit` (log plano)

Un DTO simple (`LearningEventEntry`) + interfaz + un único modelo Eloquent, sin entidad de dominio ni value objects — igual que hace `Audit` hoy con login/logout.

Desventaja: mezcla mal con que el propio roadmap ya prevé que `Learning` crezca en lógica (ENG-039); tendríamos que migrar a DDD completo de todos modos cuando eso llegue.

### Option 3 - Extender `Modules\Audit`

Reutilizar la tabla e infraestructura de `audit_logs` (`AuditLogger`/`AuditEntry`) agregando un campo de origen o un tipo de acción específico para eventos de aprendizaje.

Desventaja: mezcla dos conceptos distintos (auditoría de seguridad/accesos vs. evidencia de aprendizaje) en la misma tabla y el mismo módulo, dificultando evolucionar cada uno con sus propias reglas.

## Chosen Design

Se adopta la Opción 1.

## Module Structure

Nuevo módulo `Modules\Learning`, con la misma estructura por capas que `Academic`:

```
Modules\Learning\
  Domain\
    ValueObjects\LearningEventId.php
    ValueObjects\LearningVerb.php          (enum: LessonCompleted, ExamAttemptSubmitted)
    Entities\LearningEvent.php             (inmutable, análoga a LessonCompletion)
    Repositories\LearningEventRepository.php
  Application\
    Services\LearningEventRecorder.php     (interfaz)
    Queries\GetEnrollmentLearningEventsQuery.php
    UseCases\GetEnrollmentLearningEventsHandler.php
    Responses\LearningEventResponse.php
  Infrastructure\
    Persistence\Eloquent\Models\LearningEventModel.php
    Persistence\Eloquent\Repositories\EloquentLearningEventRepository.php
    Persistence\Migrations\..._create_learning_events_table.php
    Services\DefaultLearningEventRecorder.php   (implementa LearningEventRecorder)
    Providers\LearningServiceProvider.php
  Presentation\
    Http\Controllers\LearningEventController.php
    Routes\api.php
```

## Domain Model

`LearningEvent` (entidad inmutable, sin métodos de mutación — se crea una vez y nunca cambia):

```php
final readonly class LearningEvent
{
    public function __construct(
        public LearningEventId $id,
        public EnrollmentId $enrollmentId,
        public string $userId,
        public CourseId $courseId,
        public LearningVerb $verb,
        public string $subjectId,      // lessonId o examAttemptId, según el verbo
        public DateTimeImmutable $occurredAt,
        public array $evidence,        // metadata: timeSpentMinutes | score/percentage/passed
    ) {}
}
```

`LearningEventRepository`:

- `record(LearningEvent $event): void` (solo inserta, sin update).
- `findByEnrollmentId(EnrollmentId $enrollmentId): list<LearningEvent>` (ordenado por `occurredAt` descendente).

## Application Layer

**`LearningEventRecorder`** (interfaz en `Learning\Application\Services`, consumida por `Academic` — mismo patrón que `AuditLogger` usa hoy `Identity`):

```php
interface LearningEventRecorder
{
    public function record(
        EnrollmentId $enrollmentId,
        string $userId,
        CourseId $courseId,
        LearningVerb $verb,
        string $subjectId,
        array $evidence,
    ): void;
}
```

`DefaultLearningEventRecorder` (en `Infrastructure\Services`) construye el `LearningEvent` (`occurredAt = now()`) y llama a `LearningEventRepository::record()`. Se registra en `LearningServiceProvider`. Dependencia cruzada `Academic` → `Learning` (dirección correcta: el módulo productor del evento depende de la abstracción del módulo consumidor, igual que `Identity` depende de `AuditLogger` de `Audit`).

**Integración en los handlers existentes de `Academic`:**

- `CompleteLessonHandler` (ENG-036) gana el colaborador `LearningEventRecorder`. Tras `$this->progressRepository->save($progress)`, registra `LearningVerb::LessonCompleted` con `enrollmentId`, `courseId = $enrollment->courseId()`, `subjectId = $lessonId->value()`, `evidence = ['time_spent_minutes' => $command->timeSpentMinutes]`.

- `SubmitExamAttemptHandler` (ENG-032/033) gana dos colaboradores nuevos: `LearningEventRecorder` y `EnrollmentRepository`. Tras `$this->attempts->save($attempt)` en la rama de envío calificado (no en la rama de timeout sin grading), resuelve el enrollment del alumno para ese curso vía `findActiveOrPendingFor($exam->courseId(), $command->userId)` y registra `LearningVerb::ExamAttemptSubmitted` con `subjectId = $attempt->id()->value()`, `evidence` tomado del `GradingResult` (`score`, `percentage`, `passed`).
  - Si no hay enrollment resoluble para ese curso, **no se registra el evento** — nunca falla el envío del examen por esto.

**Lado de lectura:**

`GetEnrollmentLearningEventsQuery(enrollmentId, userId, canViewOthers)` → `GetEnrollmentLearningEventsHandler`, misma autorización que `GetEnrollmentProgressHandler`/`GetEnrollmentCurriculumStatusHandler` (dueño del enrollment o permiso `enrollments.view`, sin permiso nuevo). Devuelve `LearningEventResponse` con la lista de eventos, más reciente primero.

## Persistence

Migración `..._create_learning_events_table`:

```php
Schema::create('learning_events', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->uuid('enrollment_id');
    $table->foreign('enrollment_id')->references('id')->on('academic_enrollments')->cascadeOnDelete();
    $table->string('user_id');
    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    $table->uuid('course_id');
    $table->foreign('course_id')->references('id')->on('academic_courses')->cascadeOnDelete();
    $table->string('verb');           // 'lesson_completed' | 'exam_attempt_submitted'
    $table->string('subject_id');     // lessonId o examAttemptId
    $table->jsonb('evidence');
    $table->timestampTz('occurred_at');

    $table->index(['enrollment_id', 'occurred_at']);
});
```

FK en cascada consistente con lo ya usado en `academic_enrollments` → `users` y `academic_enrollment_lesson_completions` → `academic_lessons` (ENG-035/036). `LearningEventModel` (UUID, `$guarded = []`, casts `evidence` a array y `occurred_at` a datetime) + `EloquentLearningEventRepository::record()` (insert simple) y `findByEnrollmentId()` (select ordenado por `occurred_at desc`).

## HTTP Surface

`LearningEventController::index()`:

```
GET /api/v1/academic/enrollments/{enrollmentId}/learning-events
```

Bajo `auth:sanctum`, sin middleware de permiso adicional (autorización por pertenencia en el handler, igual que `progress`/`curriculum`). La ruta vive en `Modules\Learning\Presentation\Routes\api.php` pero bajo el mismo prefijo `academic/enrollments/{enrollmentId}` que usa `Academic`.

### Learning events response shape

```json
{
  "data": {
    "enrollment_id": "uuid",
    "events": [
      { "verb": "exam_attempt_submitted", "subject_id": "uuid", "occurred_at": "2026-08-16T10:00:00Z", "evidence": { "score": 8, "percentage": 80, "passed": true } },
      { "verb": "lesson_completed", "subject_id": "uuid", "occurred_at": "2026-08-16T09:40:00Z", "evidence": { "time_spent_minutes": 12 } }
    ]
  }
}
```

## Authorization Design

No se agregan permisos nuevos. Se reutiliza `Permission::ViewEnrollments` para que terceros con acceso ampliado consulten los eventos de aprendizaje de otro usuario, igual que en `GetEnrollmentProgressHandler`/`GetEnrollmentCurriculumStatusHandler`.

## Error Handling

- `401` → no autenticado.
- `404` → `ENROLLMENT_NOT_FOUND` (reutilizado) cuando el enrollment no existe o el solicitante no es dueño ni tiene `enrollments.view`.
- El registro de eventos nunca interrumpe la acción de negocio que lo origina: si `CompleteLessonHandler` o `SubmitExamAttemptHandler` no pueden registrar el evento (o no hay enrollment resoluble en el caso del intento de examen), la lección se completa / el examen se envía igual. No se introduce ninguna excepción nueva del lado de escritura.
- Al ser append-only no hay conflictos de "ya existe": cada evento es un registro nuevo e independiente (a diferencia de `EnrollmentProgress::completeLesson()`, que sí es idempotente).

## Out of Scope

- Endpoint de ingesta genérico para productores externos (móvil ya usa la misma API; simulador diferido a ENG-045/046).
- Eventos de inscripción (Enrollment) y de autenticación.
- Agregaciones, reportes o recomendaciones sobre estos eventos (ENG-039 y futuras historias de reportes).
- Actualización o borrado de eventos ya registrados (append-only real).

## Testing Strategy

- **Dominio** (`Learning`): `LearningEventTest` — construcción válida, inmutabilidad, value objects (`LearningEventId`, `LearningVerb`).
- **Aplicación** (`Learning`): `GetEnrollmentLearningEventsHandlerTest` — dueño, ajeno sin permiso (`EnrollmentNotFound`), ajeno con `enrollments.view`, enrollment inexistente, orden descendente por `occurredAt`.
- **Integración** (`Learning`): `EloquentLearningEventRepositoryTest` — roundtrip, orden por `occurredAt`, cascada al borrar enrollment/curso/usuario; `LearningServiceProviderTest` — registro del repositorio y del query/handler.
- **Aplicación** (`Academic`, extendiendo tests existentes): caso nuevo en `CompleteLessonHandlerTest` (se registra un `LearningEvent` con verbo `LessonCompleted` tras completar una lección) y en `SubmitExamAttemptHandlerTest` (se registra `ExamAttemptSubmitted` con el `evidence` correcto tras un envío calificado, y un caso sin enrollment resoluble donde no se registra evento pero el envío no falla).
- **Feature HTTP**: nuevo `LearningEventTest` en `Learning` (consulta propia, ajena sin/con `enrollments.view`, enrollment inexistente, orden de la lista) más un caso de extremo a extremo (HTTP → handler → recorder → repo) confirmando persistencia real del evento.

## Implementation Notes

- Mantener naming y estructura alineados con `Academic` (`CourseLessonCatalog`, `EnrollmentProgressController`) y con el precedente de dependencia cruzada `Identity` → `Audit` (`AuditLogger`).
- Usar `whereUuid()` en la ruta nueva.
- Devolver siempre el envelope `{ "data": ... }`.
- Reutilizar `EnrollmentRepository::findActiveOrPendingFor()` (ya existente desde ENG-035) para resolver el enrollment de un intento de examen; no se modifica su contrato.
- El registro de eventos es un efecto secundario best-effort: nunca debe convertirse en una fuente de fallos para `CompleteLessonHandler` ni `SubmitExamAttemptHandler`.
