# ENG-030 — Banco de preguntas (design)

Fecha: 2026-08-10
Estado: Aprobado
Historia: ENG-030 — Banco de preguntas (Fase 6 — Evaluaciones)
Incremento: Solo ENG-030. Los exámenes/cuestionarios (ENG-031), intentos (ENG-032) y el motor de calificación (ENG-033) quedan diferidos explícitamente.

## Contexto y decisiones

- Alcance: solo el banco de preguntas como catálogo de dominio.
- Tipos de pregunta: selección única, selección múltiple, verdadero/falso, asociación, ordenamiento y situacional/multimedia.
- Las preguntas se anclan **por competencia** del catálogo ENG-024 (FK a `academic_competencies`).
- CRUD completo: crear, consultar, listar (con filtro por competencia), actualizar y eliminar.
- Puntaje entero positivo por pregunta (default 1). Sin estado activo/inactivo en este incremento.
- Multimedia: solo referencias a URLs HTTPS externas validadas (sin subida de archivos ni MinIO en este incremento).
- Modelo: agregado `Question` con respuestas tipadas por tipo, persistencia en tabla única `academic_questions` con `response` JSONB, opciones normalizadas en `academic_question_options`. Este es el Enfoque 1 aprobado en el brainstorming.

## 1. Arquitectura y modelo de dominio

Arquitectura hexagonal del módulo `Modules/Academic`: Domain → Application → Infrastructure → Presentation, con CQRS (CommandBus/QueryBus).

### Agregado `Question` (Domain/Aggregates)

Campos comunes:
- `id`: `QuestionId` (UUID)
- `type`: `QuestionType` enum
- `competencyId`: `CompetencyId` (ancla al catálogo ENG-024)
- `prompt`: string (enunciado)
- `explanation`: ?string (retroalimentación)
- `score`: int positivo (default 1)
- `media`: lista opcional `[{type, url}]` de URLs HTTPS
- `options`: lista de `QuestionOption` (entidades del agregado)
- `response`: `QuestionResponse` (ValueObject tipado por tipo)

Métodos de fábrica: `create(...)` y `restore(...)`.

### `QuestionType` (Domain/Enums)

`single_choice`, `multi_select`, `true_false`, `matching`, `ordering`, `situational`.

El tipo `situational` embebe el escenario en el enunciado y reutiliza una de las otras formas de respuesta (más `media`), evitando duplicar la validación de las 5 formas.

### Entidades de opciones (Domain/Entities)

- `QuestionOption`: `id` (UUID), `side` (?string: `left`/`right` solo para matching, null para choice/ordering), `label` (string), `position` (int). Orden canónico por `position` desde 1.

### Respuestas tipadas

Interfaz `QuestionResponse` con forma canónica serializable (`toArray()`), y una implementación por tipo:
- `SingleChoiceResponse`: lista de `optionId`s + la correcta.
- `MultiSelectResponse`: lista de `optionId`s + conjunto de correctas.
- `TrueFalseResponse`: booleano correcto.
- `MatchingResponse`: pares `(leftId, rightId)`.
- `OrderingResponse`: secuencia canónica de `itemId`s.

Factory `QuestionResponseFactory` convierte el `response` JSONB ↔ ValueObject tipado según `type`.

### Invariantes de dominio

- `score` ≥ 1.
- Prompt, labels y textos no vacíos, con topes de longitud (prompt ≤ 1000, explanation ≤ 2000, label ≤ 500).
- Opciones: ≥ 2 para choice/multi/matching, ≥ 2 ítems para ordering; ids únicos; para matching, ids del lado izquierdo únicos.
- Respuesta: la/las correcta(s) pertenecen a las opciones declaradas; sin duplicados.
- `media`: URLs con la misma política de seguridad que `ExternalContentUrl` (HTTPS, longitud máxima), sin subida de archivos.
- `QuestionOption.side` solo válido para `matching`.

### Excepciones públicas (Domain/Exceptions)

- Errores de dominio con contrato público (código + estado HTTP) como el resto del módulo:
  - `InvalidQuestion` → 422, `INVALID_QUESTION`.
  - `InvalidQuestionScore` → 422, `INVALID_QUESTION_SCORE`.
  - Errores específicos por formulación de respuesta (opciones, respuesta, media) con códigos `INVALID_QUESTION_*` si hace falta.

## 2. Persistencia

Migración `2026_08_10_000002_create_academic_questions_tables` (compatible Postgres y SQLite en tests, Always-SQL):

### `academic_questions`

- `id` uuid PK
- `competency_id` uuid, FK → `academic_competencies(id)` ON DELETE CASCADE
- `type` varchar(30)
- `prompt` varchar(1000)
- `explanation` varchar(2000) nullable
- `score` integer NOT NULL, CHECK (score >= 1)
- `media` jsonb nullable — lista `[{type, url}]` con URLs HTTPS
- `response` jsonb NOT NULL — forma canónica de la respuesta según `type` (payload autoritativo que valida el dominio y persiste el repositorio tal cual; igual patrón que `snapshot` en `academic_course_versions` y `payload` en content blocks)
- `timestamps` timestamptz

### `academic_question_options`

- `id` uuid PK
- `question_id` uuid, FK → `academic_questions(id)` ON DELETE CASCADE
- `side` varchar(10) nullable (`left`/`right`)
- `label` varchar(500)
- `position` integer NOT NULL

### Repositorio

- Interfaz `QuestionRepository` (Domain/Repositories): `save`, `findById`, `all(?CompetencyId)`, `delete`.
- `EloquentQuestionRepository` (Infrastructure): implementación con `QuestionModel` + `QuestionOptionModel`, conversión `response` JSONB vía `QuestionResponseFactory`, borrado en cascada, filtro por competencia, sin N+1.
- Bind + registro de handlers en `AcademicServiceProvider`.

## 3. Capa de Aplicación (CQRS)

Comandos + handlers (validan que la competencia exista; si no, 404 público):
- `CreateQuestionCommand` / `CreateQuestionHandler` — arma la `Question` tipada y guarda.
- `UpdateQuestionCommand` / `UpdateQuestionHandler` — modifica campos editables y reemplaza `response`/opciones atómicamente; 404 si no existe.
- `DeleteQuestionCommand` / `DeleteQuestionHandler` — borra pregunta y opciones en cascada.

Queries + handlers:
- `GetQuestionQuery` / `GetQuestionHandler` — detalle con respuesta tipada.
- `ListQuestionsQuery` / `ListQuestionsHandler` — listado con filtro opcional por competencia.

Excepciones de aplicación: `QuestionNotFound` → 404, `QUESTION_NOT_FOUND`.

Respuestas públicas:
- `QuestionResponse` (Application/Responses): `id`, `type`, `competency_id`, `prompt`, `score`, `explanation`, `media`, `options` (con `side`/`position`) y `correct` (forma canónica de respuesta según tipo).
- `QuestionListItemResponse` para el listado.

La forma canónica de `correct` queda lista para el futuro motor de calificación (ENG-033).

## 4. Presentación (HTTP)

Permisos nuevos en `Permission`:
- `questions.manage` y `questions.view`, con grants en `RolePermissions`: SuperAdmin ambos; InstitutionalAdmin/Teacher/Student solo `questions.view` (mismo patrón que `courses`/`competencies`).

Rutas bajo `modules/Academic/Presentation/Routes/api.php`, grupo `auth:sanctum`:
- `POST /api/v1/academic/questions` (`questions.manage`)
- `PUT /api/v1/academic/questions/{questionId}` (`questions.manage`, `whereUuid`)
- `DELETE /api/v1/academic/questions/{questionId}` (`questions.manage`, `whereUuid`)
- `GET /api/v1/academic/questions/{questionId}` (`questions.view`, `whereUuid`)
- `GET /api/v1/academic/questions?competency_id=...` (`questions.view`)

`QuestionController` + requests `CreateQuestionRequest`, `UpdateQuestionRequest` con validación temprana (tipos de pregunta, puntaje, URLs HTTPS, topes de opciones) delegando las invariantes restantes al dominio.

`404` para pregunta o competencia inexistente y `403` sin permiso — misma semántica que el resto de Academic.

## 5. Testing

- **Unit/Domain:** `QuestionTest` (armado de cada tipo), respuestas tipadas por tipo (invariantes: correcta(s) ∈ opciones, ids únicos, pares, orden, score), URLs HTTPS de media, topes de longitud.
- **Unit/Application:** `QuestionHandlerTest` (crear/actualizar/eliminar/listar/detalle, 404s, errores de dominio sin mutar), con dobles en memoria.
- **Integration:** `EloquentQuestionRepositoryTest` (round-trip del response JSONB canónico, cascada al borrar, filtro por competencia, FK a competencia inexistente, sin N+1).
- **Feature:** `QuestionTest` HTTP (crear cada tipo, CRUD completo, 401/403/404/422, filtrado por competencia).

## Validaciones (cierre del incremento, como en ENG-029)

- Pint.
- PHPStan nivel 8 (`--no-progress --memory-limit=1G`).
- Suite completa: root `php artisan test`, módulos, y suite Academic.
- `migrate` + `migrate:status` sobre la base real.
- `route:list` para las 5 rutas nuevas.
- Commit style: `feat(academic): ...` + `docs(engineering): ...`.

## Fuera de alcance (diferido explícito)

- ENG-031 — Exámenes y cuestionarios (plantillas, aleatorización, tiempo, intentos, reglas de aprobación, retroalimentación).
- ENG-032 — Intentos de evaluación.
- ENG-033 — Motor de calificación.
- Subida de archivos multimedia (MinIO); en este incremento solo referencias HTTPS.
- Edición de competencias; las preguntas solo referencian el catálogo existente.