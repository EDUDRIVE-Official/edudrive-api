# ENG-031 — Exámenes y cuestionarios (design)

Fecha: 2026-08-11
Estado: Aprobado
Historia: ENG-031 — Exámenes y cuestionarios (Fase 6 — Evaluaciones)
Incremento: Solo la definición/configuración del examen como plantilla reutilizable. Intentos de evaluación (ENG-032), motor de calificación (ENG-033) y examen teórico de conducción (ENG-034) quedan diferidos explícitamente.

## Contexto y decisiones

- Alcance: **solo definición/configuración** del examen. No hay ejecución (iniciar intento, responder, guardar), calificación ni historial en este incremento.
- El examen se ancla **a un curso** existente (`academic_courses`, FK), consistente con el resto del contenido del curso.
- Las preguntas se **listan explícitamente** (IDs) con posición y puntos; el examen consume preguntas del banco ENG-030 (ancladas por competencia). Sin reglas de selección por competencia en este incremento.
- Retroalimentación: solo **configuración de visibilidad** (`none`/`after_submission`/`immediate`) que consumirá la `explanation` ya presente en las preguntas de ENG-030. Sin texto propio por examen.
- Intentos y reglas de aprobación: solo **configuración almacenada** (`max_attempts`, `passing_score`). La lógica de validación en ejecución queda diferida a ENG-032/033.
- Aleatorización: **flag `shuffle_questions`** (barajado de orden de preguntas por intento). El barajado real ocurre en ENG-032.
- Ciclo de vida: **sin estados** (sin draft/publicado/archivado). El examen se crea, edita y consulta directamente.
- Permisos propios `exams.manage`/`exams.view`, siguiendo el patrón de las historias previas.
- Persistencia: **tabla `academic_exams` + pivot normalizada `academic_exam_questions`** (Enfoque A aprobado en el brainstorming). Consistente con ENG-030 (opciones con posición) y con el patrón relacional del módulo.

## 1. Arquitectura y modelo de dominio

Arquitectura hexagonal del módulo `Modules/Academic`: Domain → Application → Infrastructure → Presentation, con CQRS (CommandBus/QueryBus).

### Agregado `Exam` (Domain/Aggregates)

- `id`: `ExamId` (UUID)
- `courseId`: `CourseId` (ancla al curso)
- `title`: string (≤ 180)
- `description`: ?string (≤ 2000)
- `durationMinutes`: ?int (≥ 1, null = sin límite)
- `maxAttempts`: int (≥ 1, default 1)
- `passingScore`: int (1–100, default 60)
- `shuffleQuestions`: bool (default false)
- `feedbackMode`: `ExamFeedbackMode` enum (default `none`)
- `questions`: lista ordenada de `ExamQuestion` (entidades del agregado), posición desde 1

Métodos de fábrica: `create(...)` y `restore(...)`.

### `ExamFeedbackMode` (Domain/Enums)

- `none`: no se muestra retroalimentación.
- `after_submission`: se muestra tras enviar el intento.
- `immediate`: se muestra pregunta por pregunta.

### Entidad `ExamQuestion` (Domain/Entities)

- `position`: int (desde 1, canónico)
- `questionId`: `QuestionId` (referencia al banco ENG-030)
- `points`: int (≥ 1)

### Invariantes de dominio

- `title` no vacío y ≤ 180.
- `description` ≤ 2000.
- `durationMinutes` null o ≥ 1.
- `maxAttempts` ≥ 1.
- `passingScore` entre 1 y 100.
- Al menos una pregunta; sin `questionId` duplicados; `points` ≥ 1; posiciones secuenciales desde 1.

### Excepciones públicas (Domain/Exceptions y Application/Exceptions)

- `InvalidExam` → 422, `INVALID_EXAM` (violaciones de invariantes del agregado).
- `ExamNotFound` → 404, `EXAM_NOT_FOUND` (Application).
- Reutiliza `CourseNotFound` (404, `COURSE_NOT_FOUND`) y `QuestionNotFound` (404, `QUESTION_NOT_FOUND`) existentes para validar referencias en los handlers.

## 2. Persistencia

Migración `2026_08_11_000001_create_academic_exams_tables` (compatible Postgres y SQLite en tests, Always-SQL):

### `academic_exams`

- `id` uuid PK
- `course_id` uuid FK → `academic_courses.id`, `ON DELETE CASCADE`
- `title` varchar(180) not null
- `description` text nullable
- `duration_minutes` int nullable (check null o ≥ 1)
- `max_attempts` int not null default 1
- `passing_score` smallint not null default 60
- `shuffle_questions` bool not null default false
- `feedback_mode` varchar(20) not null default 'none'
- `created_at`/`updated_at` timestamps

### `academic_exam_questions`

- `id` uuid PK
- `exam_id` uuid FK → `academic_exams.id`, `ON DELETE CASCADE`
- `question_id` uuid FK → `academic_questions.id`, `ON DELETE CASCADE`
- `position` int not null
- `points` int not null default 1
- unicidad `(exam_id, position)` y `(exam_id, question_id)`

### Repositorio Eloquent

`EloquentExamRepository` (`save`, `findById` con preguntas ordenadas, `all(?CourseId)`, `delete`), con `ExamModel`/`ExamQuestionModel`, casts (enum de feedback, bools) y carga eager de preguntas sin N+1. Borrado en cascada de preguntas del examen vía FK.

## 3. Capa de aplicación (CQRS)

- **Comandos**:
  - `CreateExamCommand(courseId, title, description?, durationMinutes?, maxAttempts, passingScore, shuffleQuestions, feedbackMode, questions: list<{questionId, points}>)`
  - `UpdateExamCommand(examId, ...)` (mismos campos, sin re-anclar `courseId`; reemplaza la lista de preguntas)
  - `DeleteExamCommand(examId)`
- **Consultas**: `GetExamQuery(examId)`, `ListExamsQuery(?courseId)`.
- **Handlers**: `CreateExamHandler` (valida curso existe → 404; construye agregado; guarda), `UpdateExamHandler` (404 si no existe; reemplaza preguntas), `DeleteExamHandler`, `GetExamHandler`, `ListExamsHandler`.
- **Responses**:
  - `ExamResponse`: id, title, course_id, description, duration_minutes, max_attempts, passing_score, shuffle_questions, feedback_mode, `questions` (en orden, con `position`, `question_id`, `points`, `ref_id` y `type` de la pregunta para el cliente).
  - `ExamListItemResponse`: id, title, course_id, question_count, passing_score. El listado omite el detalle de preguntas.
- **Bus**: registrar los 5 mensajes en `AcademicServiceProvider`.

## 4. Presentación HTTP y permisos

### Permisos (módulo Authorization)

- `Permission::ManageExams = 'exams.manage'`, `Permission::ViewExams = 'exams.view'`.
- Grants: SuperAdmin ambos; InstitutionalAdmin/Teacher/Student → `ViewExams`.

### Rutas (dentro de `auth:sanctum`, patrón ENG-030)

- Bajo `exams.view`:
  - `GET /api/v1/academic/exams` (filtro opcional `course_id`) → `index`
  - `GET /api/v1/academic/exams/{examId}` → `show` (`whereUuid`)
- Bajo `exams.manage`:
  - `POST /api/v1/academic/exams` → `store` (201)
  - `PUT /api/v1/academic/exams/{examId}` → `update` (`whereUuid`)
  - `DELETE /api/v1/academic/exams/{examId}` → `destroy` (`whereUuid`, 204)

### Requests

- `CreateExamRequest`/`UpdateExamRequest`: validación temprana (title ≤ 180, passing_score 1–100, duration_minutes ≥ 1, max_attempts ≥ 1, feedback_mode enum, `questions` array con `question_id` uuid y `points` ≥ 1, `questions.*.question_id` distinct). La consistencia total la valida el agregado.
- `ExamController` con `index`/`store`/`show`/`update`/`destroy`, normalizando `question_id` → `questionId` (patrón `ref_id` → `refId` de ENG-030).

### Feature tests

- Crear con preguntas válidas → 201 con `data.id`, `data.title`, `data.questions`.
- Curso inexistente → 404 `COURSE_NOT_FOUND`.
- Pregunta inexistente → 404 `QUESTION_NOT_FOUND`.
- `duration_minutes` 0 → 422; `passing_score` 0 o 101 → 422; `max_attempts` 0 → 422.
- Sin preguntas o pregunta duplicada → 422 `INVALID_EXAM`.
- Listado filtrado por `course_id` → solo las del curso.
- Detalle → `data.questions` en orden.
- Update (title/score/preguntas) → 200 refleja cambios.
- Delete → 204 y listado ya no lo incluye.
- 404 en get/update/delete de examen inexistente → `EXAM_NOT_FOUND`.
- 401 sin token en cada endpoint.
- Student puede listar pero 403 al crear (mutaciones inaccesibles).

## 5. Pruebas

- **Unit (dominio)**: `ExamTest` (creación, invariantes: título, duración, attempts, passing_score, preguntas únicas/orden/puntos; `ExamFeedbackModeTest` si aplica).
- **Application**: `ExamHandlerTest` (ciclo de vida completo, 404/422, filtrado por curso) con `InMemoryExamRepository`.
- **Integration**: `EloquentExamRepositoryTest` (ida y vuelta, orden de preguntas, borrado en cascada, unicidad, ausencia de N+1).
- **Feature HTTP**: `ExamTest` (casos de la sección 4).
- **Validación final**: Pint, PHPStan (nivel 8, sin errores), suite completa (root + módulos), `migrate --force` + `migrate:status`, `route:list --path=academic/exams` (5 rutas).

## Notas de riesgos y decisiones

- **`question_id` en requests:** el request usa `question_id` (snake_case) y el dominio `questionId`; el controller normaliza (patrón ya usado en ENG-030).
- **`points` por pregunta:** el agregado permite puntaje distinto por pregunta; el `passing_score` se expresa en porcentaje (1–100). La interpretación en calificación es diferida a ENG-033.
- **Cascade:** `ON DELETE CASCADE` en `academic_exams.course_id` y `academic_exam_questions.exam_id/question_id` (consistente con ENG-030).
- **Sin N+1:** el repo carga preguntas del examen con eager loading.
- **Verificación cruzada:** tests del módulo Academic siempre verdes; Pint antes de cada commit si se tocó PHP.
