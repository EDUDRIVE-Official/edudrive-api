# ENG-041 — Evidencias del Pasaporte Vial (Diseño)

## 1. Objetivo

Registrar, dentro del agregado `RoadPassport` (ENG-040), evidencia formativa objetiva a medida que ocurre en `Academic`: cursos completados y exámenes aprobados. Es la base de datos que ENG-042 (Competency Trust Model) usará para calcular confianza/nivel automáticamente — aquí solo se acumula la evidencia, sin recalcular nada todavía.

## 2. Alcance acordado con el usuario

**Incluido:** dos tipos de evidencia — `course_completed` (al completar un `Enrollment`) y `exam_passed` (al aprobar un `ExamAttempt`) — registrados de forma reactiva y automática, mismo patrón que `LearningEventRecorder` de ENG-038. Expuesta como parte de la respuesta existente del pasaporte (`RoadPassportResponse`), sin endpoint nuevo.

**Diferido explícitamente:** prácticas y simulaciones (dependen de SIMUDRIVE, sistema externo); certificaciones (sin concepto de dominio modelado); recálculo automático de nivel/confianza a partir de la evidencia acumulada (ENG-042).

## 3. Diseño

### 3.1 Dominio (`Modules\RoadPassport`)

- Enum `EvidenceType`: `CourseCompleted` (`course_completed`), `ExamPassed` (`exam_passed`).
- VO `Evidence`: `type`, `subjectId` (id del enrollment o del intento de examen — fuente de verdad para deduplicar), `courseId`, `occurredAt`, `details` (`array<string, mixed>`, libre por tipo — ej. `percentage`/`score` para `exam_passed`).
- `RoadPassport` gana `evidence: list<Evidence>` y el método `recordEvidence(Evidence $evidence): void`, **idempotente**: si ya existe una entrada con el mismo `type` + `subjectId`, no hace nada (mismo criterio que `EnrollmentProgress::completeLesson()`). No exige ningún estado particular del pasaporte (se registra igual si está `suspended`; es un hecho histórico, no una transición). `restore()` gana el parámetro `evidence`.

### 3.2 Persistencia

Tabla nueva `road_passport_evidence` (PK UUID, FK cascada a `road_passports`, `type`, `subject_id`, `course_id`, `details jsonb`, `occurred_at`). `EloquentRoadPassportRepository::save()` extiende su transacción existente: borra y reinserta también las filas de evidencia (mismo patrón ya usado para el historial).

### 3.3 Aplicación — registro reactivo

Mismo patrón exacto que `Modules\Learning\Application\Services\LearningEventRecorder` (ENG-038):

- `Modules\RoadPassport\Application\DTO\EvidenceEntry` (`userId`, `type`, `subjectId`, `courseId`, `details`).
- Contrato `Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder` (`record(EvidenceEntry $entry): void`).
- `DefaultRoadPassportEvidenceRecorder`: resuelve el pasaporte por `userId` (`RoadPassportRepository::findByUserId()`); si el usuario no tiene pasaporte emitido, **no falla, simplemente no registra nada** (la mayoría de usuarios no tendrán pasaporte todavía).
- `Modules\Academic\Application\UseCases\CompleteEnrollmentHandler` recibe `?RoadPassportEvidenceRecorder $evidenceRecorder = null` (parámetro opcional adicional, mismo patrón que `LearningEventRecorder` en `CompleteLessonHandler`) y registra `course_completed` (`subjectId` = enrollment id, `courseId`, sin `details`) tras completar el enrollment.
- `Modules\Academic\Application\UseCases\SubmitExamAttemptHandler` recibe `?RoadPassportEvidenceRecorder $evidenceRecorder = null` (nuevo parámetro opcional al final de la lista, no rompe las llamadas posicionales existentes) y registra `exam_passed` (`subjectId` = attempt id, `courseId` = `exam->courseId()`, `details` = `score`/`total_points`/`percentage`) únicamente cuando `attempt->passed() === true`, usando el mismo `EnrollmentRepository::findActiveOrPendingFor()` ya usado para resolver el curso del intento.
- Acoplamiento igual de bidireccional e intencional que el de `Academic`↔`Learning` en ENG-038: `Academic` depende de `RoadPassportEvidenceRecorder` (escritura); `RoadPassport` no depende de `Academic` en absoluto (solo recibe datos ya resueltos vía el DTO).

### 3.4 Exposición

`RoadPassportResponse::fromRoadPassport()` agrega el campo `evidence` (lista de `{type, subject_id, course_id, occurred_at, details}`), visible en `GET /road-passport/me` y `GET /road-passport/{id}` — sin endpoint ni permiso nuevo.
