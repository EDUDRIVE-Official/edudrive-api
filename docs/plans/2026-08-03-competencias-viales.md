# ENG-024 — Catálogo regional de competencias viales: Implementation Plan

> **For Codex:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar un catálogo regional jerárquico de competencias, subcompetencias e indicadores para conectar aprendizaje y evidencia vial en incrementos posteriores.

**Architecture:** El módulo `Academic` será dueño del catálogo. Las reglas jerárquicas vivirán en el dominio; la aplicación expondrá comandos y consultas mediante los buses existentes; Eloquent persistirá el árbol en PostgreSQL. Perfiles nacionales, cursos, evaluaciones y SIMUDRIVE quedan fuera de este alcance.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Laravel Sanctum, Pest, PHPStan/Larastan y Laravel Pint.

---

### Task 1: Definir el dominio y sus reglas

**Files:**
- Create: `modules/Academic/Domain/Enums/CompetencyCategory.php`
- Create: `modules/Academic/Domain/Enums/MasteryLevel.php`
- Create: `modules/Academic/Domain/ValueObjects/CompetencyId.php`
- Create: `modules/Academic/Domain/ValueObjects/CompetencyCode.php`
- Create: `modules/Academic/Domain/Aggregates/Competency.php`
- Create: `modules/Academic/Domain/Entities/Subcompetency.php`
- Create: `modules/Academic/Domain/Entities/CompetencyIndicator.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/CompetencyTest.php`

**Step 1: Write the failing test**

Crear pruebas que demuestren que una competencia nueva nace activa, admite subcompetencias con código único y rechaza indicadores duplicados dentro de la misma subcompetencia.

```php
it('agrega un indicador observable a una subcompetencia', function (): void {
    $competency = Competency::create(/* datos mínimos */);

    $competency->addSubcompetency('RISK-001.01', 'Observación del entorno');
    $competency->addIndicator('RISK-001.01', 'RISK-001.01.I01', 'Anticipa riesgos visibles.');

    expect($competency->subcompetencies()[0]->indicators())->toHaveCount(1);
});
```

**Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CompetencyTest.php`

Expected: FAIL because the aggregate and domain types do not exist.

**Step 3: Write minimal implementation**

Implementar códigos normalizados y únicos. `CompetencyCategory` inicia con `risk_management`, `road_rules`, `vehicle_control`, `vulnerable_road_users` y `eco_driving`; `MasteryLevel` inicia con `foundation`, `developing`, `proficient` y `advanced`.

```php
public function addIndicator(string $subcompetencyCode, string $code, string $description): void
{
    $this->ensureIsActive();
    $this->findSubcompetency($subcompetencyCode)->addIndicator($code, $description);
}
```

**Step 4: Run test to verify it passes**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CompetencyTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain modules/Academic/Tests/Unit/Domain
git commit -m "feat(academic): add competency catalog domain"
```

### Task 2: Persistir el catálogo jerárquico

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/<timestamp>_create_academic_competencies_tables.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CompetencyModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/SubcompetencyModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CompetencyIndicatorModel.php`
- Create: `modules/Academic/Domain/Repositories/CompetencyRepository.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCompetencyRepository.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Integration/EloquentCompetencyRepositoryTest.php`

**Step 1: Write the failing test**

Crear una competencia completa, guardarla y recuperarla; verificar categoría, nivel, orden de subcompetencias e indicadores.

**Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Integration/EloquentCompetencyRepositoryTest.php`

Expected: FAIL because repository and tables do not exist.

**Step 3: Write minimal implementation**

Usar `academic_competencies`, `academic_subcompetencies` y `academic_competency_indicators`; aplicar claves foráneas, códigos únicos y orden explícito. El repositorio reconstruye el agregado sin exponer modelos Eloquent.

```php
$table->uuid('id')->primary();
$table->string('code', 60)->unique();
$table->string('title', 180);
$table->text('description');
$table->string('category', 50);
$table->string('mastery_level', 30);
$table->string('status', 30)->index();
```

**Step 4: Run test to verify it passes**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Integration/EloquentCompetencyRepositoryTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic
git commit -m "feat(academic): persist competency catalog"
```

### Task 3: Exponer creación y consulta mediante Application

**Files:**
- Create: `modules/Academic/Application/Commands/CreateCompetencyCommand.php`
- Create: `modules/Academic/Application/Commands/AddSubcompetencyCommand.php`
- Create: `modules/Academic/Application/Commands/AddCompetencyIndicatorCommand.php`
- Create: `modules/Academic/Application/Queries/ListCompetenciesQuery.php`
- Create: `modules/Academic/Application/UseCases/CreateCompetencyHandler.php`
- Create: `modules/Academic/Application/UseCases/AddSubcompetencyHandler.php`
- Create: `modules/Academic/Application/UseCases/AddCompetencyIndicatorHandler.php`
- Create: `modules/Academic/Application/UseCases/ListCompetenciesHandler.php`
- Create: `modules/Academic/Application/Responses/CompetencyResponse.php`
- Test: `modules/Academic/Tests/Unit/Application/CreateCompetencyHandlerTest.php`

**Step 1: Write the failing test**

Probar que el handler normaliza el código, rechaza un código existente y devuelve la representación pública de la competencia creada.

**Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Unit/Application/CreateCompetencyHandlerTest.php`

Expected: FAIL because the command and handler do not exist.

**Step 3: Write minimal implementation**

Seguir el patrón de `CreateCourseCommand` y `CreateCourseHandler`. Registrar handlers en `AcademicServiceProvider`; usar DTOs de respuesta y no exponer Eloquent.

**Step 4: Run test to verify it passes**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Unit/Application/CreateCompetencyHandlerTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application modules/Academic/Infrastructure/Providers modules/Academic/Tests/Unit/Application
git commit -m "feat(academic): add competency catalog use cases"
```

### Task 4: Publicar la API protegida del catálogo

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/CompetencyController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateCompetencyRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/AddSubcompetencyRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/AddCompetencyIndicatorRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Authorization/Domain/Enums/Permission.php` (o catálogo equivalente)
- Test: `modules/Academic/Tests/Feature/CompetencyCatalogTest.php`

**Step 1: Write the failing test**

Cubrir creación, consulta jerárquica, validación de enums y rechazo de usuarios sin permisos. Usar `competencies.manage` para cambios y `competencies.view` para consulta.

**Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Feature/CompetencyCatalogTest.php`

Expected: FAIL because the routes and permissions do not exist.

**Step 3: Write minimal implementation**

Exponer:

```text
GET  /api/v1/academic/competencies
POST /api/v1/academic/competencies
POST /api/v1/academic/competencies/{competencyId}/subcompetencies
POST /api/v1/academic/competencies/{competencyId}/subcompetencies/{subcompetencyCode}/indicators
```

Aplicar `auth:sanctum` y el middleware de permisos con el patrón de Cursos. Usar el envoltorio JSON estándar de Foundation.

**Step 4: Run test to verify it passes**

Run: `docker compose exec app php artisan test modules/Academic/Tests/Feature/CompetencyCatalogTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic modules/Authorization
git commit -m "feat(academic): expose competency catalog API"
```

### Task 5: Verificar y documentar el hito

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Run full verification**

```bash
docker compose exec app composer format
docker compose exec app composer quality
```

Expected: PASS, sin errores de formato, análisis ni pruebas.

**Step 2: Document the outcome**

Actualizar ENG-024 con el alcance entregado y registrar en `ENG-LOG` endpoints, permisos, migraciones, pruebas y elementos diferidos.

**Step 3: Commit**

```bash
git add docs
git commit -m "docs(roadmap): record ENG-024 competency catalog"
```

