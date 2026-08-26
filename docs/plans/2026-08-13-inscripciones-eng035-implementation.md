# ENG-035 Enrollments Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build the full enrollment workflow for courses, including individual enrollment, bulk enrollment, direct institutional assignment, enrollment states, enrollment dates, listing/detail endpoints, and state transitions.

**Architecture:** Add a new `Enrollment` aggregate in `Modules/Academic`, persist it through a dedicated Eloquent repository, expose the workflow through CQRS handlers and HTTP endpoints, and keep `ExamAttempt` unchanged for now. Institutional assignment stays direct `organization -> user -> course`, without cohorts or groups in this version.

**Tech Stack:** PHP 8.4+, Laravel, Sanctum, Eloquent, Pest, PHPStan, Pint, PostgreSQL/SQLite-compatible migrations.

---

### Task 1: Enrollment Domain Core

**Files:**
- Create: `modules/Academic/Domain/Aggregates/Enrollment.php`
- Create: `modules/Academic/Domain/Enums/EnrollmentStatus.php`
- Create: `modules/Academic/Domain/Enums/EnrollmentSource.php`
- Create: `modules/Academic/Domain/Exceptions/InvalidEnrollment.php`
- Create: `modules/Academic/Domain/ValueObjects/EnrollmentId.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentTest.php`
- Test: `modules/Academic/Tests/Unit/Domain/ValueObjects/EnrollmentIdTest.php`

**Step 1: Write the failing tests**

Cover these behaviors:
- create valid enrollment with `individual`, `bulk`, and `institutional` source
- reject `institutional` enrollment without `organization_id`
- reject `ends_at < starts_at`
- allow `pending -> active`, `active -> completed`, `active -> canceled`
- reject invalid transitions from `completed` and `canceled`
- normalize nullable dates and preserve `enrolled_at`

**Step 2: Run tests to verify they fail**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentTest.php modules/Academic/Tests/Unit/Domain/ValueObjects/EnrollmentIdTest.php
```

Expected: FAIL because aggregate/value object do not exist yet.

**Step 3: Write minimal implementation**

Implement:
- `EnrollmentId` with UUID normalization/validation, following `CourseId` / `QuestionId` style
- `EnrollmentStatus` enum with `pending`, `active`, `completed`, `canceled`
- `EnrollmentSource` enum with `individual`, `bulk`, `institutional`
- `InvalidEnrollment` domain exception
- `Enrollment` aggregate with:
  - `create(...)`
  - `restore(...)`
  - `activate()`
  - `complete()`
  - `cancel()`
  - invariant checks for organization/date/status rules

**Step 4: Run tests to verify they pass**

Run the same test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Aggregates/Enrollment.php modules/Academic/Domain/Enums/EnrollmentStatus.php modules/Academic/Domain/Enums/EnrollmentSource.php modules/Academic/Domain/Exceptions/InvalidEnrollment.php modules/Academic/Domain/ValueObjects/EnrollmentId.php modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentTest.php modules/Academic/Tests/Unit/Domain/ValueObjects/EnrollmentIdTest.php
git commit -m "feat(academic): add enrollment domain model"
```

### Task 2: Enrollment Repository Contract and Migration

**Files:**
- Create: `modules/Academic/Domain/Repositories/EnrollmentRepository.php`
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_13_000001_create_academic_enrollments_table.php`
- Test: `modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php`

**Step 1: Write the failing integration tests**

Cover:
- save/find by id
- list with filters by `course_id`, `user_id`, `organization_id`, `status`, `source`
- update state transitions through repository save roundtrip
- reject duplicate active/pending enrollment for same `user_id + course_id`

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php
```

Expected: FAIL because repository/migration are missing.

**Step 3: Write minimal implementation**

Implement repository contract with methods:
- `save(Enrollment $enrollment): void`
- `findById(EnrollmentId $id): ?Enrollment`
- `findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment`
- `all(?CourseId $courseId = null, ?string $userId = null, ?string $organizationId = null, ?EnrollmentStatus $status = null, ?EnrollmentSource $source = null): array`

Create migration with table/indices.

**Step 4: Run tests to verify they pass**

Run the same integration test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Domain/Repositories/EnrollmentRepository.php modules/Academic/Infrastructure/Persistence/Migrations/2026_08_13_000001_create_academic_enrollments_table.php modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php
git commit -m "feat(academic): add enrollment repository contract"
```

### Task 3: Eloquent Enrollment Repository and Model

**Files:**
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/EnrollmentModel.php`
- Create: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentEnrollmentRepository.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php`

**Step 1: Extend the failing integration test**

Add assertions for:
- proper enum/date roundtrip
- active/pending duplicate detection through repository method
- stable ordering of `all()` if repo already defines one

**Step 2: Run test to verify it fails**

Run the same integration command.

Expected: FAIL because Eloquent implementation/binding do not exist.

**Step 3: Write minimal implementation**

Implement:
- Eloquent model for `academic_enrollments`
- repository mapping aggregate <-> model
- provider binding `EnrollmentRepository::class -> EloquentEnrollmentRepository::class`

Follow existing style from:
- `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php`
- `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentExamRepository.php`

**Step 4: Run tests to verify they pass**

Run the integration test again and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Infrastructure/Persistence/Eloquent/Models/EnrollmentModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentEnrollmentRepository.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php
git commit -m "feat(academic): add eloquent enrollment repository"
```

### Task 4: Individual Enrollment CQRS

**Files:**
- Create: `modules/Academic/Application/Commands/CreateEnrollmentCommand.php`
- Create: `modules/Academic/Application/Queries/GetEnrollmentQuery.php`
- Create: `modules/Academic/Application/Queries/ListEnrollmentsQuery.php`
- Create: `modules/Academic/Application/Responses/EnrollmentResponse.php`
- Create: `modules/Academic/Application/Responses/EnrollmentListItemResponse.php`
- Create: `modules/Academic/Application/UseCases/CreateEnrollmentHandler.php`
- Create: `modules/Academic/Application/UseCases/GetEnrollmentHandler.php`
- Create: `modules/Academic/Application/UseCases/ListEnrollmentsHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/EnrollmentHandlerTest.php`
- Test: `modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentTest.php`

**Step 1: Write the failing tests**

Cover:
- create individual enrollment
- reject duplicate active/pending enrollment
- reject missing course
- get by id
- list with filters
- registry/container wiring for create/get/list

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/EnrollmentHandlerTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentTest.php
```

Expected: FAIL because CQRS layer does not exist yet.

**Step 3: Write minimal implementation**

Implement the commands/queries/responses/handlers and register them in the provider.

Validation responsibilities at handler level:
- course must exist
- duplicate active/pending enrollment must be rejected

**Step 4: Run tests to verify they pass**

Run the same test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Commands/CreateEnrollmentCommand.php modules/Academic/Application/Queries/GetEnrollmentQuery.php modules/Academic/Application/Queries/ListEnrollmentsQuery.php modules/Academic/Application/Responses/EnrollmentResponse.php modules/Academic/Application/Responses/EnrollmentListItemResponse.php modules/Academic/Application/UseCases/CreateEnrollmentHandler.php modules/Academic/Application/UseCases/GetEnrollmentHandler.php modules/Academic/Application/UseCases/ListEnrollmentsHandler.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Unit/Application/EnrollmentHandlerTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentTest.php
git commit -m "feat(academic): add enrollment query and creation handlers"
```

### Task 5: Enrollment State Transitions

**Files:**
- Create: `modules/Academic/Application/Commands/ActivateEnrollmentCommand.php`
- Create: `modules/Academic/Application/Commands/CompleteEnrollmentCommand.php`
- Create: `modules/Academic/Application/Commands/CancelEnrollmentCommand.php`
- Create: `modules/Academic/Application/UseCases/ActivateEnrollmentHandler.php`
- Create: `modules/Academic/Application/UseCases/CompleteEnrollmentHandler.php`
- Create: `modules/Academic/Application/UseCases/CancelEnrollmentHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/EnrollmentLifecycleHandlerTest.php`

**Step 1: Write the failing tests**

Cover:
- `pending -> active`
- `active -> completed`
- `active -> canceled`
- invalid transitions rejected
- not found rejected

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/EnrollmentLifecycleHandlerTest.php
```

Expected: FAIL because transition commands/handlers do not exist.

**Step 3: Write minimal implementation**

Implement transition commands/handlers and register them in the provider.

**Step 4: Run tests to verify they pass**

Run the same test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Commands/ActivateEnrollmentCommand.php modules/Academic/Application/Commands/CompleteEnrollmentCommand.php modules/Academic/Application/Commands/CancelEnrollmentCommand.php modules/Academic/Application/UseCases/ActivateEnrollmentHandler.php modules/Academic/Application/UseCases/CompleteEnrollmentHandler.php modules/Academic/Application/UseCases/CancelEnrollmentHandler.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Unit/Application/EnrollmentLifecycleHandlerTest.php
git commit -m "feat(academic): add enrollment lifecycle handlers"
```

### Task 6: Bulk and Institutional Enrollment Commands

**Files:**
- Create: `modules/Academic/Application/Commands/CreateBulkEnrollmentsCommand.php`
- Create: `modules/Academic/Application/Commands/CreateInstitutionalEnrollmentCommand.php`
- Create: `modules/Academic/Application/Responses/BulkEnrollmentResponse.php`
- Create: `modules/Academic/Application/UseCases/CreateBulkEnrollmentsHandler.php`
- Create: `modules/Academic/Application/UseCases/CreateInstitutionalEnrollmentHandler.php`
- Test: `modules/Academic/Tests/Unit/Application/BulkEnrollmentHandlerTest.php`

**Step 1: Write the failing tests**

Cover:
- bulk enroll multiple users into one course
- return per-item results for duplicates/failures
- institutional enrollment requires organization id
- institutional enrollment stores `source = institutional`

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Application/BulkEnrollmentHandlerTest.php
```

Expected: FAIL because bulk/institutional commands do not exist.

**Step 3: Write minimal implementation**

Implement:
- one-course bulk enrollment with per-user processing
- direct institutional enrollment using `OrganizationId`

Do not add cohorts, batching jobs, or async processing.

**Step 4: Run tests to verify they pass**

Run the same test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Application/Commands/CreateBulkEnrollmentsCommand.php modules/Academic/Application/Commands/CreateInstitutionalEnrollmentCommand.php modules/Academic/Application/Responses/BulkEnrollmentResponse.php modules/Academic/Application/UseCases/CreateBulkEnrollmentsHandler.php modules/Academic/Application/UseCases/CreateInstitutionalEnrollmentHandler.php modules/Academic/Tests/Unit/Application/BulkEnrollmentHandlerTest.php
git commit -m "feat(academic): add bulk and institutional enrollments"
```

### Task 7: Enrollment HTTP API

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/EnrollmentController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateEnrollmentRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateBulkEnrollmentsRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateInstitutionalEnrollmentRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/ActivateEnrollmentRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CompleteEnrollmentRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CancelEnrollmentRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentTest.php`

**Step 1: Write the failing feature tests**

Cover:
- create individual enrollment
- create bulk enrollment
- create institutional enrollment
- list and show
- activate / complete / cancel
- authentication and permission separation
- duplicate conflict
- validation for dates and organization id

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php
```

Expected: FAIL because controller/requests/routes do not exist.

**Step 3: Write minimal implementation**

Implement controller + requests + routes:
- `POST /api/v1/academic/enrollments`
- `POST /api/v1/academic/enrollments/bulk`
- `POST /api/v1/academic/enrollments/institutional`
- `GET /api/v1/academic/enrollments`
- `GET /api/v1/academic/enrollments/{enrollmentId}`
- `POST /api/v1/academic/enrollments/{enrollmentId}/activate`
- `POST /api/v1/academic/enrollments/{enrollmentId}/complete`
- `POST /api/v1/academic/enrollments/{enrollmentId}/cancel`

**Step 4: Run tests to verify they pass**

Run the same feature test command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Http/Requests/CreateEnrollmentRequest.php modules/Academic/Presentation/Http/Requests/CreateBulkEnrollmentsRequest.php modules/Academic/Presentation/Http/Requests/CreateInstitutionalEnrollmentRequest.php modules/Academic/Presentation/Http/Requests/ActivateEnrollmentRequest.php modules/Academic/Presentation/Http/Requests/CompleteEnrollmentRequest.php modules/Academic/Presentation/Http/Requests/CancelEnrollmentRequest.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "feat(academic): add enrollment api"
```

### Task 8: Enrollment Permissions

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Test: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentTest.php`

**Step 1: Write/extend the failing tests**

Cover:
- `enrollments.view`
- `enrollments.manage`
- role mapping for `SuperAdmin`, `InstitutionalAdmin`, `Teacher`, `Student`
- HTTP forbids operations without required permission

**Step 2: Run test to verify it fails**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php modules/Academic/Tests/Feature/EnrollmentTest.php
```

Expected: FAIL because permissions do not exist yet.

**Step 3: Write minimal implementation**

Add new permissions and map them to roles.

Recommended initial policy:
- `SuperAdmin`: view + manage
- `InstitutionalAdmin`: view + manage
- `Teacher`: view
- `Student`: none

**Step 4: Run tests to verify they pass**

Run the same command and expect PASS.

**Step 5: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "feat(authorization): add enrollment permissions"
```

### Task 9: Full Verification and Docs

**Files:**
- Modify: `docs/engineering/ENG-LOG.md`
- Modify: `docs/engineering/SESION.md`
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`

**Step 1: Run focused enrollment suite**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/EnrollmentTest.php modules/Academic/Tests/Unit/Domain/ValueObjects/EnrollmentIdTest.php modules/Academic/Tests/Unit/Application/EnrollmentHandlerTest.php modules/Academic/Tests/Unit/Application/EnrollmentLifecycleHandlerTest.php modules/Academic/Tests/Unit/Application/BulkEnrollmentHandlerTest.php modules/Academic/Tests/Integration/EloquentEnrollmentRepositoryTest.php modules/Academic/Tests/Integration/AcademicServiceProviderEnrollmentTest.php modules/Academic/Tests/Feature/EnrollmentTest.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
```

Expected: PASS.

**Step 2: Run static analysis and formatting**

Run:
```bash
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic modules/Authorization
MSYS_NO_PATHCONV=1 docker run --rm --network edudrive_edudrive-network -w /var/www/html -v "D:\vr506\EDUDRIVE\edudrive-api:/var/www/html" edudrive-app php vendor/bin/pint modules/Academic modules/Authorization
```

Expected: PHPStan no errors; Pint clean or auto-fixes.

**Step 3: Update docs**

Document:
- ENG-035 completed implementation status
- next natural dependency for `ENG-036`
- operational notes if any migration/route verification was needed

**Step 4: Commit**

```bash
git add docs/engineering/ENG-LOG.md docs/engineering/SESION.md docs/roadmap/ENG-000-roadmap-tecnico-backend.md
git commit -m "docs(engineering): update enrollment progress logs"
```
