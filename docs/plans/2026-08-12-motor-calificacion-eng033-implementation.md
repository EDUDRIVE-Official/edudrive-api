# ENG-033 — Motor de calificación: Plan de implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar un motor de calificación configurable sobre `ExamAttempt` que reemplace el cálculo básico actual por grading detallado con breakdown por pregunta y por competencia, reutilizable por futuras experiencias de evaluación.

**Architecture:** Hexagonal dentro de `Modules\Academic` (Domain → Application → Infrastructure → Presentation), manteniendo `ExamAttempt` como dueño del ciclo de vida del intento y extrayendo la lógica de grading a un servicio de dominio `ExamAttemptGrader`. El snapshot del intento se enriquece con `competency_id`, y el resultado final se persiste en el intento como materialización JSON (`grading_breakdown`, `competency_results`) además de los campos agregados `score`, `total_points`, `percentage` y `passed`.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, PostgreSQL / SQLite :memory:, Pest, PHPStan nivel 8, Pint.

---

**Referencias de patrón (ya disponibles):** `modules/Academic/Domain/Aggregates/ExamAttempt.php`, `modules/Academic/Domain/Entities/AttemptQuestion.php`, `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`, `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamAttemptRepository.php`, `modules/Academic/Application/Responses/ExamAttemptResponse.php`, `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`, `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`, `modules/Academic/Tests/Feature/ExamAttemptTest.php`. Respuestas tipadas ENG-030/032: `SingleChoiceResponse`, `MultiSelectResponse`, `TrueFalseResponse`, `MatchingResponse`, `OrderingResponse`, `QuestionResponse`, `QuestionResponseFactory`.

**CLI (siempre en contenedor desechable):**
```
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan <cmd>
```
- PHPStan: `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- Pint: `php vendor/bin/pint`
- Tests: `php artisan test <archivo>`

---

### Task 1: Ampliar esquema de intentos para resultados detallados

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_12_000002_add_grading_breakdown_to_academic_exam_attempts.php`
- Test: `modules/Foundation/Tests/Unit/DomainExceptionTest.php`

**Step 1: Write the migration**

Agregar a `academic_exam_attempts`:

- `grading_breakdown` JSON/JSONB nullable
- `competency_results` JSON/JSONB nullable

Mantener compatibilidad con PostgreSQL y SQLite siguiendo el patrón de migraciones previas del módulo.

**Step 2: Run migration to verify it applies**

Run: `php artisan migrate --force`
Expected: la nueva migración se aplica sin errores.

**Step 3: Verify SQLite test DB still rebuilds**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS.

---

### Task 2: Enriquecer el snapshot con `competency_id`

**Files:**
- Modify: `modules/Academic/Domain/Entities/AttemptQuestion.php`
- Modify: `modules/Academic/Application/UseCases/StartExamAttemptHandler.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamAttemptRepository.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptQuestionModel.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php`
- Test: `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`

**Step 1: Write the failing unit test**

Agregar caso en `AttemptQuestionTest` que exija:

- `competencyId()` disponible en create/restore
- serialización persistible del dato dentro del snapshot

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php`
Expected: FAIL por constructor/método inexistente.

**Step 3: Implement minimal domain change**

- Añadir `CompetencyId` a `AttemptQuestion`
- Ajustar `create()` y `restore()`
- Añadir accessor `competencyId()`

**Step 4: Propagate snapshot creation and persistence**

- `StartExamAttemptHandler` debe copiar `competency_id` desde la pregunta del banco
- el modelo/repo Eloquent deben guardar y rehidratar `competency_id`

**Step 5: Run focused tests**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionTest.php modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`
Expected: PASS.

---

### Task 3: Introducir objetos de grading de dominio

**Files:**
- Create: `modules/Academic/Domain/Entities/AttemptQuestionGrade.php`
- Create: `modules/Academic/Domain/Entities/CompetencyGrade.php`
- Create: `modules/Academic/Domain/ValueObjects/GradingPolicy.php`
- Create: `modules/Academic/Domain/ValueObjects/GradingResult.php`
- Test: `modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionGradeTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/GradingPolicyTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/GradingResultTest.php`

**Step 1: Write failing tests**

Cubrir:

- construcción válida de breakdown por pregunta
- construcción válida de breakdown por competencia
- política con flags `allowPartialCredit` y `applyPenalties`
- resultado con `score`, `totalPoints`, `percentage`, `passed`, `questionBreakdown`, `competencyBreakdown`

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Entities/AttemptQuestionGradeTest.php modules/Academic/Tests/Unit/Domain/ValueObjects/GradingPolicyTest.php modules/Academic/Tests/Unit/Domain/ValueObjects/GradingResultTest.php`
Expected: FAIL (clases no encontradas).

**Step 3: Implement the minimal objects**

- clases readonly/inmutables
- `toArray()` en breakdown/result para persistencia/response

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 4: Implementar `ExamAttemptGrader` con score base y breakdown completo

**Files:**
- Create: `modules/Academic/Domain/Services/ExamAttemptGrader.php`
- Test: `modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php`

**Step 1: Write the failing test**

Cubrir como mínimo:

- score total y porcentaje correctos
- `passed` según `passing_score`
- breakdown por pregunta
- breakdown por competencia
- no score negativo

Incluir al menos 3 escenarios:

- todo correcto
- respuestas parcialmente correctas
- respuestas con penalización sin bajar de cero

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php`
Expected: FAIL (servicio no existe).

**Step 3: Implement minimal grader**

- evaluar por tipo de pregunta
- calcular puntos logrados por pregunta
- agrupar por competencia
- devolver `GradingResult`

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 5: Añadir partial credit y penalizaciones por tipo

**Files:**
- Modify: `modules/Academic/Domain/Services/ExamAttemptGrader.php`
- Test: `modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php`

**Step 1: Extend failing tests**

Agregar casos específicos:

- `multi_select` con intersección correcta y selección inválida
- `matching` con parcial por pares correctos
- `ordering` con parcial por posición correcta

**Step 2: Run test to verify it fails for the new cases**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php`
Expected: FAIL en los nuevos escenarios.

**Step 3: Implement minimal support per type**

- aplicar `allowPartialCredit`
- aplicar `applyPenalties`
- garantizar score mínimo 0 por pregunta

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 6: Integrar el grader en `ExamAttempt` y `SubmitExamAttemptHandler`

**Files:**
- Modify: `modules/Academic/Domain/Aggregates/ExamAttempt.php`
- Modify: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Modify: `modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php`
- Modify: `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`

**Step 1: Write failing tests**

Cubrir que:

- `submit()` aplica `GradingResult`
- el intento persiste breakdown detallado
- el handler usa grading completo en lugar del cálculo básico anterior

**Step 2: Run tests to verify they fail**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
Expected: FAIL por API faltante o expectativas nuevas.

**Step 3: Implement minimal integration**

- `ExamAttempt` debe aceptar y exponer breakdowns del grading
- `SubmitExamAttemptHandler` construye una `GradingPolicy`, invoca `ExamAttemptGrader` y aplica el resultado

**Step 4: Run tests to verify they pass**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 7: Persistir y rehidratar `grading_breakdown` y `competency_results`

**Files:**
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamAttemptRepository.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ExamAttemptModel.php`
- Modify: `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`

**Step 1: Write the failing integration test**

Agregar caso que exija:

- guardar grading detallado al submit
- rehidratarlo sin pérdida

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`
Expected: FAIL porque los campos JSON aún no se guardan/rehidratan.

**Step 3: Implement minimal persistence**

- guardar arrays serializados en `grading_breakdown` y `competency_results`
- rehidratar hacia arrays/VOs según la API elegida en el agregado

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 8: Exponer el grading ampliado en responses y HTTP

**Files:**
- Modify: `modules/Academic/Application/Responses/ExamAttemptResponse.php`
- Modify: `modules/Academic/Application/UseCases/GetExamAttemptHandler.php`
- Modify: `modules/Academic/Application/UseCases/SubmitExamAttemptHandler.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/ExamAttemptController.php`
- Modify: `modules/Academic/Tests/Feature/ExamAttemptTest.php`

**Step 1: Write the failing feature test**

Agregar assertions para:

- `submit` devuelve `grading_breakdown`
- `show` devuelve `competency_results` cuando aplica
- ocultamiento sigue respetando `feedback_mode` y permisos

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: FAIL porque la response aún no expone los campos.

**Step 3: Implement minimal response changes**

- ampliar `toArray()` de `ExamAttemptResponse`
- no abrir endpoints nuevos

**Step 4: Run test to verify it passes**

Run el mismo comando del Step 2.
Expected: PASS.

---

### Task 9: Verificación focalizada de ENG-033

**Files:**
- Modify: archivos tocados si Pint/PHPStan requieren ajustes

**Step 1: Run focused suites**

Run: `php artisan test modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php modules/Academic/Tests/Feature/ExamAttemptTest.php`
Expected: PASS.

**Step 2: Run PHPStan**

Run: `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic`
Expected: `[OK] No errors`.

**Step 3: Run Pint**

Run: `php vendor/bin/pint modules/Academic modules/Authorization`
Expected: formato limpio.

**Step 4: Re-run touched suites after Pint if needed**

Run el mismo comando del Step 1.
Expected: PASS.

---

### Task 10: Validación operativa

**Files:** ninguno (solo ejecutar)

**Step 1: Verify migrations**

Run: `php artisan migrate --force`
Expected: migración de grading aplicada.

Run: `php artisan migrate:status`
Expected: la migración nueva aparece `Ran`.

**Step 2: Verify routes still hold**

Run: `php artisan route:list --path=academic/exam-attempts`
Expected: siguen siendo 6 rutas.

**Step 3: Smoke full root suite**

Run: `php artisan test`
Expected: PASS.

---

### Task 11: Cierre documental

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Update roadmap**

- marcar `ENG-033` como `Completado`
- añadir nota de cierre siguiendo el estilo de ENG-031/032
- actualizar historia técnica activa
- añadir nueva entrada de changelog

**Step 2: Update ENG-LOG**

- añadir `IMP-033` con secciones de completado, validaciones y estado

**Step 3: Verify docs references**

Run: `php artisan test modules/Foundation/Tests/Unit/DomainExceptionTest.php`
Expected: PASS (sanity after docs-only step in same branch).

---

Plan complete and saved to `docs/plans/2026-08-12-motor-calificacion-eng033-implementation.md`. Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?
