# ENG-034 — Examen teorico de conduccion Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar la primera experiencia backend de examen teorico de conduccion reutilizando `Exam`, `ExamAttempt` y `ExamAttemptGrader`, con categorias de licencia, banco oficial autorizado, reglas configurables, historial teorico y recomendaciones de estudio.

**Architecture:** El incremento se construye dentro de `Modules\Academic` extendiendo `Question` y `Exam` con metadata teorica, sin crear agregados paralelos para intentos o grading. La experiencia teorica agrega handlers, queries, responses y rutas especializadas encima del flujo ya validado de examenes e intentos.

**Tech Stack:** PHP 8.4, Laravel 12, Eloquent, PostgreSQL / SQLite :memory:, Pest, PHPStan nivel 8, Pint.

---

**Referencias de patron (ya disponibles):** `modules/Academic/Domain/Aggregates/Question.php`, `modules/Academic/Domain/Aggregates/Exam.php`, `modules/Academic/Domain/Aggregates/ExamAttempt.php`, `modules/Academic/Application/UseCases/CreateExamHandler.php`, `modules/Academic/Application/UseCases/StartExamAttemptHandler.php`, `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`, `modules/Academic/Application/Responses/QuestionResponse.php`, `modules/Academic/Application/Responses/ExamResponse.php`, `modules/Academic/Application/Responses/ExamAttemptResponse.php`, `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentQuestionRepository.php`, `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamRepository.php`, `modules/Academic/Tests/Feature/ExamTest.php`, `modules/Academic/Tests/Feature/ExamAttemptTest.php`.

**CLI (siempre en contenedor desechable):**
```
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan <cmd>
```
- PHPStan: `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- Pint: `php vendor/bin/pint`
- Tests: `php artisan test <archivo>`

**Nota operativa:** el usuario pidio no crear commits por ahora. Mantener checkpoints logicos, pero no ejecutar `git commit` durante esta fase salvo instruccion explicita posterior.

---

### Task 1: Ampliar esquema de preguntas con metadata teorica

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_12_000004_add_theory_metadata_to_academic_questions.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/QuestionModel.php`
- Test: `modules/Foundation/Tests/Unit/DomainExceptionTest.php`

**Step 1: Write the migration**

Agregar a `academic_questions`:

- `source_kind` string con default `custom`
- `source_reference` string nullable
- `license_categories` JSON/JSONB con default lista vacia

Mantener compatibilidad con PostgreSQL y SQLite siguiendo el patron ya usado en Academic.

**Step 2: Run migration to verify it applies**

Run: `php artisan migrate --force`
Expected: la migracion se aplica sin errores.

**Step 3: Verify SQLite test DB still rebuilds**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

---

### Task 2: Enriquecer `Question` con origen oficial y categorias de licencia

**Files:**
- Modify: `modules/Academic/Domain/Aggregates/Question.php`
- Create: `modules/Academic/Domain/Enums/QuestionSourceKind.php`
- Create: `modules/Academic/Domain/ValueObjects/LicenseCategory.php`
- Modify: `modules/Academic/Application/Responses/QuestionResponse.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentQuestionRepository.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/QuestionModel.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php`
- Test: `modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php`

**Step 1: Write the failing unit tests**

Agregar casos que exijan:

- `Question` expone `sourceKind()`, `sourceReference()` y `licenseCategories()`
- normaliza `source_reference` opcional
- rechaza categorias repetidas o vacias
- permite preguntas `custom` sin categorias y preguntas `official` con categorias validas

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php`
Expected: FAIL por API/clases inexistentes.

**Step 3: Implement minimal domain changes**

- crear `QuestionSourceKind`
- crear `LicenseCategory`
- ampliar `Question::create()/restore()/replace()`
- agregar accessors y validaciones minimas

**Step 4: Propagate persistence and response**

- guardar/rehidratar los nuevos campos en el repositorio Eloquent
- exponerlos en `QuestionResponse::toArray()`

**Step 5: Run focused tests**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php`
Expected: PASS.

---

### Task 3: Extender HTTP de preguntas para metadata teorica

**Files:**
- Modify: `modules/Academic/Presentation/Http/Requests/CreateQuestionRequest.php`
- Modify: `modules/Academic/Presentation/Http/Requests/UpdateQuestionRequest.php`
- Modify: `modules/Academic/Tests/Feature/QuestionCrudTest.php`

**Step 1: Write the failing feature tests**

Cubrir que:

- se puede crear pregunta oficial con `source_kind`, `source_reference` y `license_categories`
- se valida `license_categories.*` como string no vacio
- la respuesta HTTP devuelve la metadata teorica

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/QuestionCrudTest.php`
Expected: FAIL en payload/response nuevos.

**Step 3: Implement minimal request validation**

- permitir `source_kind` con enum
- permitir `source_reference` nullable
- permitir `license_categories` como array de strings no vacios

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 4: Ampliar esquema de examenes con metadata teorica y reglas de grading

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_12_000005_add_theory_metadata_to_academic_exams.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamModel.php`
- Test: `modules/Foundation/Tests/Unit/DomainExceptionTest.php`

**Step 1: Write the migration**

Agregar a `academic_exams`:

- `kind` string con default `standard`
- `license_category` string nullable
- `allow_partial_credit` boolean default `false`
- `apply_penalties` boolean default `false`

**Step 2: Run migration to verify it applies**

Run: `php artisan migrate --force`
Expected: la migracion se aplica sin errores.

**Step 3: Verify SQLite test DB still rebuilds**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

---

### Task 5: Enriquecer `Exam` con tipo teorico y validaciones de banco autorizado

**Files:**
- Modify: `modules/Academic/Domain/Aggregates/Exam.php`
- Create: `modules/Academic/Domain/Enums/ExamKind.php`
- Modify: `modules/Academic/Application/Responses/ExamResponse.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamRepository.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamModel.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`
- Test: `modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`

**Step 1: Write the failing unit tests**

Agregar casos que exijan:

- `Exam` expone `kind()`, `licenseCategory()`, `allowPartialCredit()` y `applyPenalties()`
- `kind = theory` exige `licenseCategory`
- `kind = standard` no exige `licenseCategory`
- roundtrip del repo conserva la metadata nueva

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
Expected: FAIL por API/clases nuevas.

**Step 3: Implement minimal domain and persistence changes**

- crear `ExamKind`
- ampliar `Exam::create()/restore()/replace()`
- guardar/rehidratar metadata nueva en Eloquent
- ampliar `ExamResponse`

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 6: Validar examenes teoricos contra preguntas oficiales y categoria

**Files:**
- Modify: `modules/Academic/Application/UseCases/CreateExamHandler.php`
- Modify: `modules/Academic/Application/UseCases/UpdateExamHandler.php`
- Create: `modules/Academic/Application/Exceptions/InvalidTheoryExam.php`
- Test: `modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`
- Test: `modules/Academic/Tests/Feature/ExamTest.php`

**Step 1: Write the failing tests**

Cubrir que:

- un examen `theory` rechaza preguntas `custom`
- un examen `theory` rechaza preguntas sin la categoria del examen en `license_categories`
- un examen `standard` sigue permitiendo preguntas no oficiales

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamHandlerTest.php modules/Academic/Tests/Feature/ExamTest.php`
Expected: FAIL en las expectativas nuevas.

**Step 3: Implement minimal application validation**

- al materializar las preguntas del examen, validar `source_kind = official` cuando `kind = theory`
- validar pertenencia de la categoria del examen en `license_categories`
- traducir el rechazo a error publico especifico

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 7: Extender HTTP de examenes con metadata teorica y reglas de grading

**Files:**
- Modify: `modules/Academic/Presentation/Http/Requests/CreateExamRequest.php`
- Modify: `modules/Academic/Presentation/Http/Requests/UpdateExamRequest.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/ExamController.php`
- Test: `modules/Academic/Tests/Feature/ExamTest.php`

**Step 1: Extend the failing feature tests**

Agregar assertions para:

- crear examen `theory` con `kind`, `license_category`, `allow_partial_credit`, `apply_penalties`
- listar/detalle de examen devuelven esos campos
- validacion HTTP de `kind` y `license_category`

**Step 2: Run test to verify it fails for new fields**

Run: `php artisan test modules/Academic/Tests/Feature/ExamTest.php`
Expected: FAIL por request/response faltantes.

**Step 3: Implement minimal request/controller wiring**

- aceptar y validar los campos nuevos
- no romper comportamiento legacy de examenes `standard`

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 8: Construir `GradingPolicy` desde configuracion del examen

**Files:**
- Modify: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Modify: `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
- Modify: `modules/Academic/Tests/Feature/ExamAttemptTest.php`

**Step 1: Write the failing tests**

Cubrir que:

- un examen teorico con `allow_partial_credit = true` usa parciales al enviar
- un examen teorico con `apply_penalties = true` habilita penalizaciones del grader
- examenes `standard` conservan defaults previos

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: FAIL en la configuracion nueva de la politica.

**Step 3: Implement minimal policy selection**

- construir `GradingPolicy` desde el `Exam` asociado
- reutilizar el grader actual sin bifurcar el flujo de submit

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 9: Introducir recomendaciones basicas de estudio desde el grading

**Files:**
- Create: `modules/Academic/Application/Responses/StudyRecommendationResponse.php`
- Create: `modules/Academic/Application/Services/TheoryStudyRecommendationService.php`
- Modify: `modules/Academic/Application/Responses/ExamAttemptResponse.php`
- Modify: `modules/Academic/Application/UseCases/GetExamAttemptHandler.php`
- Modify: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
- Test: `modules/Academic/Tests/Feature/ExamAttemptTest.php`

**Step 1: Write the failing tests**

Cubrir que:

- un intento teorico `submitted` devuelve `study_recommendations`
- las recomendaciones se ordenan por peor desempeno de competencia
- un intento `canceled` no expone recomendaciones
- un intento `standard` no expone recomendaciones por defecto

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: FAIL por respuesta/campo/servicio inexistente.

**Step 3: Implement minimal recommendation service**

- derivar recomendaciones desde `competency_results` y `grading_breakdown`
- priorizar score/percentage mas bajos
- exponer estructura serializable en `ExamAttemptResponse`

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 10: Agregar endpoints especializados de examenes teoricos

**Files:**
- Create: `modules/Academic/Application/Queries/ListTheoryExamsQuery.php`
- Create: `modules/Academic/Application/Queries/GetTheoryExamQuery.php`
- Create: `modules/Academic/Application/Commands/StartTheoryExamSimulationCommand.php`
- Create: `modules/Academic/Application/UseCases/ListTheoryExamsHandler.php`
- Create: `modules/Academic/Application/UseCases/GetTheoryExamHandler.php`
- Create: `modules/Academic/Application/UseCases/StartTheoryExamSimulationHandler.php`
- Create: `modules/Academic/Presentation/Http/Controllers/TheoryExamController.php`
- Create: `modules/Academic/Presentation/Http/Requests/StartTheoryExamSimulationRequest.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Integration/AcademicServiceProviderTheoryExamTest.php`
- Test: `modules/Academic/Tests/Feature/TheoryExamTest.php`

**Step 1: Write the failing tests**

Cubrir como minimo:

- listar solo examenes `kind = theory`
- obtener detalle de un examen teorico
- iniciar simulacion teorica solo sobre examen `theory`
- rechazar usar el endpoint especializado sobre examen `standard`

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Integration/AcademicServiceProviderTheoryExamTest.php modules/Academic/Tests/Feature/TheoryExamTest.php`
Expected: FAIL por handlers/controlador/rutas inexistentes.

**Step 3: Implement minimal specialized API**

- registrar mensajes nuevos en `MessageHandlerRegistry`
- agregar controlador y rutas bajo `api/v1/academic/theory-exams`
- delegar el inicio de simulacion al flujo existente de intentos

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 11: Exponer historial teorico por usuario y categoria

**Files:**
- Create: `modules/Academic/Application/Queries/ListTheoryExamAttemptsQuery.php`
- Create: `modules/Academic/Application/Responses/TheoryExamAttemptListItemResponse.php`
- Create: `modules/Academic/Application/UseCases/ListTheoryExamAttemptsHandler.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/TheoryExamController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Unit/Application/ListTheoryExamAttemptsHandlerTest.php`
- Test: `modules/Academic/Tests/Feature/TheoryExamTest.php`

**Step 1: Write the failing tests**

Cubrir que:

- el estudiante obtiene su historial teorico
- se puede filtrar por `license_category`
- roles con `exam_attempts.view` pueden consultar el historial de otro usuario
- examenes `standard` no aparecen en el historial teorico

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Application/ListTheoryExamAttemptsHandlerTest.php modules/Academic/Tests/Feature/TheoryExamTest.php`
Expected: FAIL por query/response/endpoint inexistentes.

**Step 3: Implement minimal history query**

- reutilizar `ExamRepository` + `ExamAttemptRepository`
- filtrar por examenes `kind = theory`
- aplicar filtro opcional por categoria

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 12: Verificacion focalizada de ENG-034

**Files:**
- Modify: archivos tocados si Pint/PHPStan requieren ajustes

**Step 1: Run focused suites**

Run: `php artisan test modules/Academic/Tests/Feature/QuestionCrudTest.php modules/Academic/Tests/Feature/ExamTest.php modules/Academic/Tests/Feature/ExamAttemptTest.php modules/Academic/Tests/Feature/TheoryExamTest.php modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php modules/Academic/Tests/Unit/Application/ExamHandlerTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php modules/Academic/Tests/Unit/Application/ListTheoryExamAttemptsHandlerTest.php modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php modules/Academic/Tests/Integration/AcademicServiceProviderTheoryExamTest.php`
Expected: PASS.

**Step 2: Run PHPStan**

Run: `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic modules/Authorization`
Expected: `[OK] No errors`.

**Step 3: Run Pint**

Run: `php vendor/bin/pint modules/Academic modules/Authorization`
Expected: formato limpio.

**Step 4: Re-run touched suites after Pint if needed**

Run el mismo comando del Step 1.
Expected: PASS.

---

### Task 13: Validacion operativa

**Files:** ninguno (solo ejecutar)

**Step 1: Verify migrations**

Run: `php artisan migrate --force`
Expected: migraciones teoricas aplicadas.

Run: `php artisan migrate:status`
Expected: las migraciones `000004` y `000005` aparecen `Ran`.

**Step 2: Verify routes**

Run: `php artisan route:list --path=academic/theory-exams`
Expected: rutas especializadas de examenes teoricos registradas.

Run: `php artisan route:list --path=academic/theory-attempts`
Expected: ruta de historial teorico registrada.

**Step 3: Smoke root suite**

Run: `php artisan test`
Expected: PASS.

---

### Task 14: Cierre documental

**Files:**
- Modify: `docs/engineering/SESION.md`
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Update session continuity**

- mover `ENG-034` de siguiente historia a historia en ejecucion o validacion segun corresponda
- registrar decisiones de arquitectura teorica

**Step 2: Update roadmap**

- cambiar `ENG-034` a `Completado` cuando todo este verificado
- anadir nota de cierre al estilo `ENG-031/032/033`

**Step 3: Update ENG-LOG**

- anadir entrada `IMP-034` con completado, validaciones y estado

**Step 4: Docs sanity check**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

---

Plan complete and saved to `docs/plans/2026-08-12-examen-teorico-conduccion-eng034-implementation.md`. Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?
