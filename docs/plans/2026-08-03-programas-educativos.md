# ENG-025 — Programas educativos regionales: Implementation Plan

> **For Codex:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar programas educativos regionales que segmenten audiencias y organicen cursos publicados en una secuencia reutilizable.

**Architecture:** El módulo `Academic` será dueño del agregado `EducationalProgram`. El dominio controlará audiencia, orden de cursos y ciclo de vida; Application validará la existencia y publicación de los cursos; Eloquent persistirá el agregado en tablas normalizadas. Perfiles nacionales, propiedad organizacional, módulos, lecciones y versionado quedan fuera del alcance.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Laravel Sanctum, Pest, PHPStan/Larastan, Laravel Pint y Vite.

---

## Preparación del worktree

Trabajar desde:

```powershell
Set-Location 'C:\Users\vr506\Documents\EDUDRIVE\edudrive-api\.worktrees\eng-025-programas-educativos'
$eng025Root = (Get-Location).Path
$dependencyRoot = 'C:\Users\vr506\Documents\EDUDRIVE\edudrive-api'
```

El contenedor persistente `edudrive-app` puede montar otro checkout. Todas las verificaciones de este plan deben usar un contenedor efímero con el worktree explícito:

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "${eng025Root}:/var/www/html" --volume "${dependencyRoot}\vendor:/var/www/html/vendor" --volume "${dependencyRoot}\.env:/var/www/html/.env:ro" edudrive-app php artisan test <ruta-de-prueba>
```

Antes de la verificación integral ejecutar:

```powershell
npm ci
npm run build
```

Esto genera el manifest Vite ignorado que necesitan las pruebas web preexistentes.

### Task 1: Definir audiencia y agregado del programa

**Files:**
- Create: `modules/Academic/Domain/Enums/ProgramStatus.php`
- Create: `modules/Academic/Domain/Enums/LicenseStage.php`
- Create: `modules/Academic/Domain/Enums/ProgramContext.php`
- Create: `modules/Academic/Domain/Enums/VehicleType.php`
- Create: `modules/Academic/Domain/ValueObjects/ProgramId.php`
- Create: `modules/Academic/Domain/ValueObjects/ProgramCode.php`
- Create: `modules/Academic/Domain/ValueObjects/ProgramAudience.php`
- Create: `modules/Academic/Domain/Entities/ProgramCourse.php`
- Create: `modules/Academic/Domain/Aggregates/EducationalProgram.php`
- Create: `modules/Academic/Domain/Exceptions/ArchivedProgramCannotBeModified.php`
- Create: `modules/Academic/Domain/Exceptions/ProgramRequiresCourses.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/EducationalProgramTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/ProgramAudienceTest.php`

**Step 1: Write the failing audience tests**

Cover valid optional ages, rejection of negative ages, rejection when `minAge > maxAge`, enum de-duplication and stable ordering.

```php
it('combina criterios regionales de audiencia', function (): void {
    $audience = ProgramAudience::fromValues(
        minAge: 16,
        maxAge: 18,
        licenseStages: [LicenseStage::Learner, LicenseStage::Learner],
        contexts: [ProgramContext::General],
        vehicleTypes: [VehicleType::Motorcycle],
    );

    expect($audience->minAge())->toBe(16)
        ->and($audience->maxAge())->toBe(18)
        ->and($audience->licenseStages())->toBe([LicenseStage::Learner]);
});
```

**Step 2: Run tests to verify RED**

Run the container command with `modules/Academic/Tests/Unit/Domain/ValueObjects/ProgramAudienceTest.php`.

Expected: FAIL because `ProgramAudience` and its enums do not exist.

**Step 3: Implement the audience value object**

Enums:

```php
enum LicenseStage: string
{
    case Unlicensed = 'unlicensed';
    case Learner = 'learner';
    case Licensed = 'licensed';
    case Professional = 'professional';
}

enum ProgramContext: string
{
    case General = 'general';
    case Institutional = 'institutional';
    case Corporate = 'corporate';
}

enum VehicleType: string
{
    case Motorcycle = 'motorcycle';
    case Automobile = 'automobile';
}
```

`ProgramAudience::fromValues()` must reject ages below zero and reversed ranges, de-duplicate enums by their string value and expose lists reindexed with `array_values()`.

**Step 4: Verify audience GREEN**

Run the same audience test.

Expected: PASS.

**Step 5: Write the failing aggregate tests**

Cover:

- New program starts in `draft`.
- Replacing courses preserves input order and rejects duplicate `CourseId` values.
- Publishing with no courses raises `ProgramRequiresCourses`.
- Publishing changes status and timestamp.
- Archiving makes audience/course mutations invalid.

```php
$program->replaceCourses([
    CourseId::fromString('019c2600-0000-7000-8000-000000000001'),
    CourseId::fromString('019c2600-0000-7000-8000-000000000002'),
]);

expect($program->courses()[0]->position())->toBe(1)
    ->and($program->courses()[1]->position())->toBe(2);
```

**Step 6: Run aggregate tests to verify RED**

Run the container command with `modules/Academic/Tests/Unit/Domain/Aggregates/EducationalProgramTest.php`.

Expected: FAIL because the aggregate does not exist.

**Step 7: Implement the minimal aggregate**

Use `ProgramId` and `ProgramCode` patterns consistent with `CompetencyId`/`CompetencyCode`. `EducationalProgram` exposes `create()`, `restore()`, `changeAudience()`, `replaceCourses()`, `publish(DateTimeImmutable $at)`, `archive(DateTimeImmutable $at)` and accessors.

`replaceCourses()` must build `ProgramCourse` positions starting at 1 and reject duplicate IDs before mutating state. `publish()` validates only the aggregate invariant (non-empty sequence); validation of course existence/status belongs to Application.

**Step 8: Verify aggregate GREEN**

Run both Task 1 test files.

Expected: PASS.

**Step 9: Commit**

```bash
git add modules/Academic/Domain modules/Academic/Tests/Unit/Domain
git commit -m "feat(academic): add educational program domain"
```

### Task 2: Persistir el agregado normalizado

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_03_000002_create_academic_programs_tables.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ProgramModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ProgramCourseModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ProgramLicenseStageModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ProgramContextModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/ProgramVehicleTypeModel.php`
- Create: `modules/Academic/Domain/Repositories/ProgramRepository.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentProgramRepository.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Integration/EloquentProgramRepositoryTest.php`

**Step 1: Write the failing integration test**

Create a program with every audience dimension and two courses, save it and reload it. Assert code, ages, enum lists, status, course IDs and positions. Also cover `existsByCode()` and ordered `all()`.

**Step 2: Run test to verify RED**

Run the container command with `modules/Academic/Tests/Integration/EloquentProgramRepositoryTest.php`.

Expected: FAIL because repository and tables do not exist.

**Step 3: Create the normalized migration**

`academic_programs`:

```php
$table->uuid('id')->primary();
$table->string('code', 60)->unique();
$table->string('title', 180);
$table->text('description');
$table->unsignedSmallInteger('min_age')->nullable();
$table->unsignedSmallInteger('max_age')->nullable();
$table->string('status', 30)->index();
$table->timestampTz('published_at')->nullable();
$table->timestampTz('archived_at')->nullable();
$table->timestampsTz();
```

`academic_program_courses` stores a UUID primary key, `program_id`, `course_id`, `position`, timestamps, unique `(program_id, course_id)` and unique `(program_id, position)`. Both IDs use foreign keys within Academic; course deletion is restricted while a program references it. Application still validates course existence to produce the official domain error rather than leaking a database exception.

Each targeting table stores `program_id` plus its enum value and has a composite unique constraint. All program-owned child rows cascade on program deletion.

**Step 4: Implement models and repository**

`ProgramRepository` exposes `save`, `findById`, `findByCode`, `existsByCode` and `all`.

`EloquentProgramRepository::save()` must use one database transaction, upsert the root, replace owned targeting/course rows and preserve order. `toDomain()` eagerly loads children ordered by position/value and passes reindexed lists to `restore()`.

Register the interface binding in `AcademicServiceProvider::register()`.

**Step 5: Run integration test to verify GREEN**

Expected: PASS.

**Step 6: Commit**

```bash
git add modules/Academic
git commit -m "feat(academic): persist educational programs"
```

### Task 3: Crear y listar programas mediante los buses

**Files:**
- Create: `modules/Academic/Application/Commands/CreateProgramCommand.php`
- Create: `modules/Academic/Application/Queries/ListProgramsQuery.php`
- Create: `modules/Academic/Application/UseCases/CreateProgramHandler.php`
- Create: `modules/Academic/Application/UseCases/ListProgramsHandler.php`
- Create: `modules/Academic/Application/Responses/ProgramResponse.php`
- Create: `modules/Academic/Application/Exceptions/ProgramCodeAlreadyExists.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Unit/Application/CreateProgramHandlerTest.php`

**Step 1: Write failing handler tests**

Use an in-memory `ProgramRepository`. Verify normalized code, generated UUID, audience mapping, public array shape and duplicate-code conflict.

```php
$response = $handler->handle(new CreateProgramCommand(
    code: ' moto-learner-01 ',
    title: 'Aprendices de motocicleta',
    description: 'Programa regional inicial.',
    minAge: 16,
    maxAge: 18,
    licenseStages: ['learner'],
    contexts: ['general'],
    vehicleTypes: ['motorcycle'],
));

expect($response->toArray()['code'])->toBe('MOTO-LEARNER-01');
```

**Step 2: Run test to verify RED**

Expected: FAIL because command and handler do not exist.

**Step 3: Implement commands, handlers and response**

`CreateProgramHandler` converts input strings with `Enum::from()`, builds `ProgramAudience`, checks `existsByCode`, saves and returns `ProgramResponse`.

`ProgramCodeAlreadyExists` extends the Foundation `DomainException` with status 409 and code `PROGRAM_CODE_ALREADY_EXISTS`.

`ProgramResponse::toArray()` must have a complete PHPDoc array shape containing root fields, audience lists, timestamps and ordered courses:

```php
[
    'id' => '...',
    'code' => 'MOTO-LEARNER-01',
    'title' => '...',
    'description' => '...',
    'status' => 'draft',
    'audience' => [
        'min_age' => 16,
        'max_age' => 18,
        'license_stages' => ['learner'],
        'contexts' => ['general'],
        'vehicle_types' => ['motorcycle'],
    ],
    'courses' => [],
    'published_at' => null,
    'archived_at' => null,
]
```

Register both handlers in `AcademicServiceProvider::boot()`.

**Step 4: Run handler tests to verify GREEN**

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application modules/Academic/Infrastructure/Providers modules/Academic/Tests/Unit/Application
git commit -m "feat(academic): add educational program queries"
```

### Task 4: Actualizar audiencia y secuencia de cursos

**Files:**
- Create: `modules/Academic/Application/Commands/ChangeProgramAudienceCommand.php`
- Create: `modules/Academic/Application/Commands/ReplaceProgramCoursesCommand.php`
- Create: `modules/Academic/Application/UseCases/ChangeProgramAudienceHandler.php`
- Create: `modules/Academic/Application/UseCases/ReplaceProgramCoursesHandler.php`
- Create: `modules/Academic/Application/Exceptions/ProgramNotFound.php`
- Create: `modules/Academic/Application/Exceptions/CourseNotFoundForProgram.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php`

**Step 1: Write failing course replacement tests**

Use in-memory program/course repositories. Cover:

- Replaces and reorders existing courses.
- Rejects an unknown course ID with `CourseNotFoundForProgram` (404, `PROGRAM_COURSE_NOT_FOUND`).
- Does not save a partially changed aggregate when validation fails.
- Domain rejects duplicate IDs.

**Step 2: Run test to verify RED**

Expected: FAIL because handler does not exist.

**Step 3: Implement minimal handlers**

`ReplaceProgramCoursesHandler` must validate every ID through `CourseRepository::findById()` before calling `replaceCourses()`. It does not require courses to be published yet.

`ChangeProgramAudienceHandler` loads the program, builds `ProgramAudience`, invokes `changeAudience()`, saves and returns `ProgramResponse`.

`ProgramNotFound` returns 404 and code `PROGRAM_NOT_FOUND`.

Register both commands.

**Step 4: Run Task 4 tests to verify GREEN**

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic
git commit -m "feat(academic): manage program audience and courses"
```

### Task 5: Publicar y archivar programas

**Files:**
- Create: `modules/Academic/Application/Commands/PublishProgramCommand.php`
- Create: `modules/Academic/Application/Commands/ArchiveProgramCommand.php`
- Create: `modules/Academic/Application/UseCases/PublishProgramHandler.php`
- Create: `modules/Academic/Application/UseCases/ArchiveProgramHandler.php`
- Create: `modules/Academic/Application/Exceptions/ProgramCourseNotPublished.php`
- Create: `modules/Academic/Domain/Exceptions/ProgramAlreadyPublished.php`
- Create: `modules/Academic/Domain/Exceptions/ProgramAlreadyArchived.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php`

**Step 1: Write failing publication tests**

Cover:

- Publishes when all referenced courses are published.
- Rejects an empty program.
- Rejects any draft or archived course using `PROGRAM_COURSE_NOT_PUBLISHED` with status 422.
- Rejects publishing twice.
- Archives draft or published programs.
- Rejects archiving twice.

**Step 2: Run test to verify RED**

Expected: FAIL because publication handlers do not exist.

**Step 3: Implement publication validation**

The handler loads the program, iterates `ProgramCourse` references, loads each course, requires `CourseStatus::Published`, then calls `$program->publish(new DateTimeImmutable())` and saves. Keep time injectable through a small callable/clock only if the project already has a clock abstraction; otherwise assert non-null timestamps rather than exact current time.

All public exceptions extend `Modules\Foundation\Domain\Exceptions\DomainException`.

Register publish/archive commands.

**Step 4: Run Task 5 tests to verify GREEN**

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic
git commit -m "feat(academic): publish and archive programs"
```

### Task 6: Publicar la API protegida

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/ProgramController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateProgramRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/ChangeProgramAudienceRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/ReplaceProgramCoursesRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Modify: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`
- Test: `modules/Academic/Tests/Feature/EducationalProgramTest.php`

**Step 1: Write failing permission tests**

Add `ManagePrograms`/`ViewPrograms` expectations. SuperAdmin gets both; the other roles get only view.

Run `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`.

Expected: FAIL because enum cases do not exist.

**Step 2: Write failing Feature tests**

Cover the full HTTP flow:

1. Create two courses and publish them.
2. Create a program.
3. Change audience.
4. Replace course order.
5. Publish.
6. List and assert nested order.
7. Archive and reject further mutation.

Also cover enum validation, invalid age ranges, authentication, `programs.view`, `programs.manage`, duplicate code, unknown course and non-published course.

Run `modules/Academic/Tests/Feature/EducationalProgramTest.php`.

Expected: FAIL with 404 because routes do not exist.

**Step 3: Implement permissions**

Add:

```php
case ManagePrograms = 'programs.manage';
case ViewPrograms = 'programs.view';
```

Extend `RolePermissions` using the same distribution as Courses and Competencies.

**Step 4: Implement Form Requests**

Use Laravel `Enum` validation for lists:

```php
'license_stages' => ['array'],
'license_stages.*' => [new Enum(LicenseStage::class)],
'contexts' => ['array'],
'contexts.*' => [new Enum(ProgramContext::class)],
'vehicle_types' => ['array'],
'vehicle_types.*' => [new Enum(VehicleType::class)],
'course_ids' => ['required', 'array', 'distinct'],
'course_ids.*' => ['required', 'uuid'],
```

Validate `min_age`/`max_age` as nullable integers `min:0`; keep cross-field ordering as a domain invariant so both HTTP and non-HTTP entry points share it.

**Step 5: Implement controller and routes**

Controller dispatches only through `CommandBus`/`QueryBus` and serializes `ProgramResponse`.

Routes:

```text
GET   /api/v1/academic/programs                         programs.view
POST  /api/v1/academic/programs                         programs.manage
PATCH /api/v1/academic/programs/{programId}/audience    programs.manage
PUT   /api/v1/academic/programs/{programId}/courses     programs.manage
POST  /api/v1/academic/programs/{programId}/publish     programs.manage
POST  /api/v1/academic/programs/{programId}/archive     programs.manage
```

All routes require `auth:sanctum`; constrain `{programId}` with `whereUuid()`.

**Step 6: Run permission and Feature tests to verify GREEN**

Expected: PASS.

**Step 7: Run all new ENG-025 tests together**

Run the container command with:

```text
modules/Academic/Tests/Unit/Domain/ValueObjects/ProgramAudienceTest.php
modules/Academic/Tests/Unit/Domain/Aggregates/EducationalProgramTest.php
modules/Academic/Tests/Integration/EloquentProgramRepositoryTest.php
modules/Academic/Tests/Unit/Application/CreateProgramHandlerTest.php
modules/Academic/Tests/Unit/Application/ReplaceProgramCoursesHandlerTest.php
modules/Academic/Tests/Unit/Application/PublishProgramHandlerTest.php
modules/Academic/Tests/Feature/EducationalProgramTest.php
modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
```

Expected: PASS.

**Step 8: Commit**

```bash
git add modules/Academic modules/Authorization
git commit -m "feat(academic): expose educational program API"
```

### Task 7: Verificar y documentar ENG-025

**Files:**
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`
- Modify: `docs/engineering/ENG-LOG.md`

**Step 1: Generate frontend build for preexisting web tests**

```powershell
npm ci
npm run build
```

Expected: Vite creates `public/build/manifest.json`.

**Step 2: Run formatter**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "${eng025Root}:/var/www/html" --volume "${dependencyRoot}\vendor:/var/www/html/vendor" --volume "${dependencyRoot}\.env:/var/www/html/.env:ro" edudrive-app composer format
```

Review all modifications; only ENG-025 files should change.

**Step 3: Run full quality**

```powershell
docker run --rm --env COMPOSER_PROCESS_TIMEOUT=600 --network edudrive_edudrive-network --workdir /var/www/html --volume "${eng025Root}:/var/www/html" --volume "${dependencyRoot}\vendor:/var/www/html/vendor" --volume "${dependencyRoot}\.env:/var/www/html/.env:ro" edudrive-app composer quality
```

Expected: Pint PASS, PHPStan reports no errors, and all tests pass.

**Step 4: Inspect schema and routes**

```powershell
docker run --rm --network edudrive_edudrive-network --workdir /var/www/html --volume "${eng025Root}:/var/www/html" --volume "${dependencyRoot}\vendor:/var/www/html/vendor" --volume "${dependencyRoot}\.env:/var/www/html/.env:ro" edudrive-app php artisan route:list --path=api/v1/academic/programs
```

Expected: six protected program routes.

**Step 5: Document outcome**

Mark ENG-025 completed in the roadmap and add an `IMP-026` entry to ENG-LOG with:

- Aggregate, audience dimensions and lifecycle.
- Five normalized persistence tables in total: root, ordered courses and three audience vocabularies.
- Six endpoints and two permissions.
- Test/quality counts from the fresh run.
- Explicit deferrals: organization ownership, country profiles/license categories, modules/lecciones, evaluation/SIMUDRIVE associations, enrollment/progress and versioning.

**Step 6: Commit code formatting if needed**

If Pint changed committed implementation files:

```bash
git add modules/Academic modules/Authorization
git commit -m "style(academic): format educational programs"
```

**Step 7: Commit documentation**

```bash
git add docs
git commit -m "docs(roadmap): record ENG-025 educational programs"
```

## Definition of done

- Programas regionales combinan edad, etapa de licencia, contexto y vehículo.
- Programas ordenan cursos existentes sin duplicados.
- Publicación requiere al menos un curso y todos publicados.
- Archivo vuelve el programa inmutable.
- Persistencia reconstruye el agregado completo en orden.
- API usa buses, autenticación y permisos estándar.
- No existe `organization_id` ni categoría legal por país.
- No se implementan módulos, lecciones, evaluaciones, SIMUDRIVE ni versionado.
- Pint, PHPStan y suite completa pasan con evidencia fresca.
- Roadmap y ENG-LOG quedan actualizados.
