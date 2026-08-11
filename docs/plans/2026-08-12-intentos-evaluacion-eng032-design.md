# ENG-032 — Intentos de evaluación (design)

Fecha: 2026-08-12
Estado: Aprobado
Historia: ENG-032 — Intentos de evaluación (Fase 6 — Evaluaciones)
Incremento: Ejecución de un intento de examen sobre el agregado `Exam` de ENG-031: inicio, respuestas, guardado progresivo, finalización, resultado básico y prevención de duplicados. La calificación fina (ENG-033) y el examen teórico de conducción (ENG-034) quedan diferidos explícitamente.

## Contexto y decisiones

- El intento pertenece a un **estudiante** identificado como UUID del usuario autenticado (FK directa a `users`, módulo Identity). No existen inscripciones aún (ENG-035 diferido), por lo que la autorización por curso/inscripción llega después.
- El intento **captura un snapshot inmutable** del examen (configuración + preguntas con prompt/options/respuesta correcta/explicación) al iniciar. Si el examen se edita después, los intentos en curso y el historial quedan intactos.
- **Barajado:** si `shuffle_questions` está activo, al iniciar se baraja el orden de preguntas y se persiste ese orden en el snapshot del intento (orden fijo durante todo el intento y registrado para el historial).
- **Resultado básico:** el intento calcula y persiste `score`, `total_points`, `percentage` y `passed` al finalizar. El motor de calificación fino (penalizaciones, parciales, competencias, reglas configurables) es ENG-033.
- **Puntaje:** por cada pregunta acertada se suman los `points` definidos en el snapshot del examen. Porcentaje = score / total_points × 100. Aprobado si percentage ≥ passing_score del snapshot. Todo o nada por pregunta.
- **Acierto:** se añade `matches()` a cada respuesta tipada de ENG-030 (SingleChoice/TrueFalse por igualdad directa; MultiSelect por conjunto; Matching por pares sin importar orden; Ordering por orden exacto).
- **Retroalimentación:** `is_correct`, `correct_response` y `explanation` siempre se persisten; su *exposición* depende del `feedback_mode` del snapshot y del rol que consulta (Student vs `exam_attempts.view`).
- **Prevención de duplicados:** un solo intento activo (`in_progress`) por (exam, usuario), `submit` único, y respeto de `max_attempts` contando intentos finalizados (submitted/canceled).
- **Tiempo límite:** `started_at` se persiste; al `submit` se valida contra `duration_minutes` (si definido). Si expiró, el submit marca el intento como `canceled`.
- **Permisos:** el estudiante gestiona sus propios intentos (dueño). El permiso `exam_attempts.view` (SuperAdmin/InstitutionalAdmin/Teacher) permite consultar historial e intentos de terceros. Student no lo tiene.
- **Persistencia:** Enfoque A aprobado en el brainstorming: 2 tablas normalizadas `academic_exam_attempts` + `academic_exam_attempt_questions`, consistente con ENG-031 y con el patrón relacional del módulo.

## 1. Arquitectura y modelo de dominio

Arquitectura hexagonal del módulo `Modules/Academic`: Domain → Application → Infrastructure → Presentation, con CQRS (CommandBus/QueryBus).

### Agregado `ExamAttempt` (Domain/Aggregates)

- `id`: `ExamAttemptId` (UUID)
- `examId`: `ExamId` (referencia al examen plantilla)
- `userId`: UUID (estudiante, FK → `users`)
- `status`: enum `ExamAttemptStatus` (`in_progress` | `submitted` | `canceled`)
- `startedAt`: datetime
- `submittedAt`: ?datetime
- Snapshot de configuración (inmutable): `title`, `durationMinutes` (nullable), `passingScore` (1–100), `shuffleQuestions` (bool), `feedbackMode` (enum)
- `questions`: lista ordenada de entidades `AttemptQuestion` (snapshot en el orden barajado persistido)
- Resultado (calculado en `submit`): `score` (int), `totalPoints` (int), `percentage` (int), `passed` (bool)

Métodos de fábrica: `start(...)` (genera snapshot con barajado) y `restore(...)`.

### `ExamAttemptStatus` (Domain/Enums)

- `in_progress`
- `submitted`
- `canceled`

### Entidad `AttemptQuestion` (Domain/Entities)

- `id`: `AttemptQuestionId` (UUID de la fila)
- `position` (int, desde 1)
- `questionId`: `QuestionId` (referencia al banco ENG-030)
- `points` (int ≥ 1)
- Snapshot de la pregunta: `prompt`, `type` (QuestionType), `options`, `correctResponse` (QuestionResponse tipada), `explanation` (nullable)
- Estado del intento: `userResponse` (?QuestionResponse), `isCorrect` (?bool), `answeredAt` (?datetime)

### Invariantes de dominio

- Solo se puede responder o enviar un intento `in_progress`.
- No se puede responder una posición fuera del snapshot ni con un tipo distinto al de la pregunta.
- `submit()` es único; un re-submit es rechazado.
- Al `submit()`, si `duration_minutes` está definido y `started_at + duration` < ahora, el intento pasa a `canceled`.
- `answer()` sobrescribe la respuesta anterior mientras `in_progress` (guardado progresivo, last-write-wins).
- La unicidad de un activo por (exam, usuario) y el respeto de `max_attempts` se validan en el handler con concurrencia (transacción + lock).

### Excepciones públicas

- `InvalidExamAttempt` → 422, `INVALID_EXAM_ATTEMPT` (violaciones de invariantes del agregado).
- `ExamAttemptNotFound` → 404, `EXAM_ATTEMPT_NOT_FOUND` (Application).
- `ExamAttemptLimitReached` → 409, `EXAM_ATTEMPT_LIMIT_REACHED` (activo duplicado o `max_attempts`).
- `ExamAttemptAlreadySubmitted` → 409, `EXAM_ATTEMPT_ALREADY_SUBMITTED` (re-submit).
- Reutiliza `ExamNotFound` (404, `EXAM_NOT_FOUND`) para validar el examen en `StartExamAttemptHandler`.

## 2. Persistencia

Migración `2026_08_12_000001_create_academic_exam_attempt_tables` (compatible Postgres y SQLite en tests, Always-SQL):

### `academic_exam_attempts`

- `id` uuid PK
- `exam_id` uuid FK → `academic_exams.id`, `ON DELETE CASCADE`
- `user_id` uuid FK → `users.id`, `ON DELETE CASCADE`
- `status` varchar(20) not null
- `started_at` timestamptz not null
- `submitted_at` timestamptz nullable
- `title` varchar(180) not null (snapshot)
- `duration_minutes` int nullable (check null o ≥ 1)
- `passing_score` smallint not null
- `shuffle_questions` bool not null
- `feedback_mode` varchar(20) not null
- `score` int not null default 0
- `total_points` int not null default 0
- `percentage` int not null default 0
- `passed` bool not null default false
- `created_at`/`updated_at` timestamps
- Índice parcial único `(exam_id, user_id)` donde `status = 'in_progress'` (único activo a nivel BD)

### `academic_exam_attempt_questions`

- `id` uuid PK
- `attempt_id` uuid FK → `academic_exam_attempts.id`, `ON DELETE CASCADE`
- `position` int not null
- `question_id` uuid FK → `academic_questions.id`
- `points` int not null
- `prompt` text not null
- `type` varchar(20) not null
- `options` jsonb nullable
- `correct_response` jsonb not null
- `explanation` text nullable
- `user_response` jsonb nullable
- `is_correct` bool nullable
- `answered_at` timestamptz nullable
- Unicidad `(attempt_id, position)` y `(attempt_id, question_id)`

### Repositorio Eloquent

`EloquentExamAttemptRepository` (`save`, `findById` con preguntas ordenadas y eager loading sin N+1, `findActiveFor(examId, userId)`, `countCompletedFor(examId, userId)`), con `ExamAttemptModel`/`ExamAttemptQuestionModel`, casts (enum status/feedback_mode, jsonb de responses/options). Reconstrucción vía `ExamAttempt::restore`. El `save` persiste intento + preguntas en transacción.

## 3. Capa de aplicación (CQRS)

- **Comandos**:
  - `StartExamAttemptCommand(examId, userId)`: valida examen (404), activo único y `max_attempts` (409), carga preguntas del banco para el snapshot (con barajado si aplica), guarda.
  - `AnswerAttemptQuestionCommand(attemptId, userId, position, QuestionResponse)`: guarda/sobrescribe la respuesta si `in_progress`; valida pertenencia (404) y tipo/posición (422).
  - `SubmitExamAttemptCommand(attemptId, userId)`: finaliza (timeout → `canceled`; calcula score/percentage/passed; `submitted`).
  - `CancelExamAttemptCommand(attemptId, userId)`: cancela un intento `in_progress`.
- **Consultas**: `GetExamAttemptQuery(attemptId, userId, role)`, `ListExamAttemptsQuery(?examId, ?userId, ?status)`.
- **Handlers**: `StartExamAttemptHandler`, `AnswerAttemptQuestionHandler`, `SubmitExamAttemptHandler`, `CancelExamAttemptHandler`, `GetExamAttemptHandler`, `ListExamAttemptsHandler`.
- **Responses**:
  - `ExamAttemptResponse`: id, exam_id, user_id, status, started_at, submitted_at, score, total_points, percentage, passed, y `questions` (position, question_id, type, points, prompt, options, user_response; más `is_correct`/`correct_response`/`explanation` según feedback_mode y rol).
  - `ExamAttemptListItemResponse`: id, exam_id, user_id, status, started_at, submitted_at, score, percentage, passed (sin preguntas).
- **Acierto**: método `matches()` en cada respuesta tipada de ENG-030 (Domain/Entities/Responses), usado por el agregado en `submit`.
- **Bus**: registrar los 6 mensajes en `AcademicServiceProvider`.

## 4. Presentación HTTP y permisos

### Permisos (módulo Authorization)

- `Permission::ViewExamAttempts = 'exam_attempts.view'`.
- Grants: SuperAdmin, InstitutionalAdmin, Teacher → `ViewExamAttempts`. Student no.
- El estudiante accede a sus propios intentos por pertenencia (`user_id` = usuario autenticado), sin requerir permiso.

### Rutas (dentro de `auth:sanctum`, patrón ENG-031, prefijo `api/v1/academic`)

- `POST /exam-attempts` → `start` (201) — cualquier autenticado.
- `PUT /exam-attempts/{attemptId}/questions/{position}` → `answer` (`whereUuid` en attemptId) — dueño.
- `POST /exam-attempts/{attemptId}/submit` → `submit` — dueño.
- `POST /exam-attempts/{attemptId}/cancel` → `cancel` — dueño.
- `GET /exam-attempts/{attemptId}` → `show` — dueño o `exam_attempts.view`.
- `GET /exam-attempts` → `index` — `exam_attempts.view` (filtros `exam_id`, `user_id`, `status`).

### Requests

- `StartExamAttemptRequest`: `exam_id` (uuid required). `authorize()` → true.
- `AnswerAttemptQuestionRequest`: `response` (array required), shape validado contra el tipo de la posición en el snapshot (reutiliza `fromArray` de ENG-030). `authorize()` → dueño.
- Submit/Cancel: sin body.

### Controller

`ExamAttemptController` con `start`/`answer`/`submit`/`cancel`/`show`/`index`. Dueño derivado de `$request->user()`; pertenencia validada en los handlers (404 si no es del usuario); `show`/`index` aplican regla dueño-o-permiso. Normalización `response` → `QuestionResponse` vía `QuestionResponseFactory` (existente en ENG-030).

### Feature tests

- Iniciar → 201 con `data.id`, `data.status` = `in_progress`, preguntas en orden.
- Examen inexistente → 404 `EXAM_NOT_FOUND`.
- Segundo activo o exceder `max_attempts` → 409 `EXAM_ATTEMPT_LIMIT_REACHED`.
- Responder por tipo de pregunta → 200 y `user_response` persistida.
- Responder intento no `in_progress` o de otro usuario → 422/404.
- Tipo incorrecto o posición fuera del snapshot → 422 `INVALID_EXAM_ATTEMPT`.
- Submit → 200 con score/percentage/passed; re-submit → 409 `EXAM_ATTEMPT_ALREADY_SUBMITTED`.
- Timeout en submit → `canceled`.
- Show: dueño ve su intento; Student con feedback `none` sin is_correct/explanation; Teacher con permiso sí; Student ajeno → 404.
- Index: Teacher lista historial con filtros; Student sin permiso → 403.
- 401 sin token en cada endpoint.

## 5. Pruebas

- **Unit (dominio)**: `ExamAttemptTest` (start con snapshot y barajado, invariantes, submit con cálculo y timeout, restore). `ExamAttemptStatusTest` si aplica.
- **Unit (aplicación)**: `ExamAttemptHandlerTest` con `InMemoryExamAttemptRepository` (ciclo completo, 404/409/422, reglas de feedback en Get).
- **Integration**: `EloquentExamAttemptRepositoryTest` (ida y vuelta con preguntas, unicidad parcial del activo, borrado en cascada, conteo para `max_attempts`, ausencia de N+1).
- **Feature HTTP**: `ExamAttemptTest` (casos de la sección 4).
- **Validación final**: Pint, PHPStan (nivel 8, sin errores), suite completa (root + módulos), `migrate --force` + `migrate:status`, `route:list --path=academic/exam-attempts` (6 rutas).

## Notas de riesgos y decisiones

- **Snapshot inmutable:** el intento copia configuración y preguntas al iniciar; ediciones posteriores del examen no afectan intentos en curso.
- **Concurrencia en `start`:** unicidad del activo garantizada con índice parcial único + transacción con lock.
- **Feedback:** `is_correct`/`correct_response`/`explanation` siempre se persisten; la exposición depende de `feedback_mode` y del rol.
- **`max_attempts`:** se cuenta sobre intentos finalizados (submitted/canceled), no sobre el activo.
- **Calificación fina** (penalizaciones, parciales, competencias, reglas configurables) diferida a ENG-033; aquí solo puntaje básico todo-o-nada por pregunta.
- **`matches()` en respuestas tipadas de ENG-030:** cambio en archivos existentes de ENG-030 (SingleChoiceResponse, MultiSelectResponse, TrueFalseResponse, MatchingResponse, OrderingResponse); debe preservar comportamiento y tests.
- **Verificación cruzada:** tests del módulo Academic siempre verdes; Pint antes de cada commit si se tocó PHP.
