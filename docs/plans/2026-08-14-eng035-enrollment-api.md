# ENG-035 Enrollment HTTP API and Permissions Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Expose the enrollment use cases over HTTP with validation, permission gates, and feature coverage for individual, bulk, institutional, query, and lifecycle operations.

**Architecture:** Keep business rules in existing application handlers and domain exceptions, and make the HTTP layer a thin adapter made of route groups, `FormRequest` validation, and a single `EnrollmentController`. Add `enrollments.view` and `enrollments.manage` to the authorization model so the new routes follow the same middleware-based protection pattern already used by Academic.

**Tech Stack:** Laravel 12, Pest, Sanctum, custom `CommandBus`/`QueryBus`, PHP 8.2+, module-based architecture under `modules/Academic` and `modules/Authorization`

---

### Task 1: Add enrollment permissions to authorization

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Test: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

**Step 1: Write the failing permission tests**

Add assertions for the two new permissions:

```php
it('otorga permisos de enrollments al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewEnrollments))->toBeTrue();
});

it('otorga manage y view de enrollments al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageEnrollments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageEnrollments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewEnrollments))->toBeFalse();
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

Expected: FAIL because `Permission::ManageEnrollments` and `Permission::ViewEnrollments` do not exist yet.

**Step 3: Write minimal implementation**

Extend the enum and role mapping:

```php
enum Permission: string
{
    // ...
    case ViewEnrollments = 'enrollments.view';
    case ManageEnrollments = 'enrollments.manage';
}
```

```php
Role::SuperAdmin => [
    // ...
    Permission::ViewEnrollments,
    Permission::ManageEnrollments,
],
Role::InstitutionalAdmin => [
    // existing view permissions...
    Permission::ViewEnrollments,
    Permission::ManageEnrollments,
],
Role::Teacher => [
    // existing view permissions...
    Permission::ViewEnrollments,
],
Role::Student => [
    // no enrollment permissions
],
```

Keep `InstitutionalAdmin` and `Teacher` split if needed instead of sharing one `match` arm.

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
git commit -m "feat(authorization): add enrollment permissions"
```

### Task 2: Write failing enrollment feature coverage

**Files:**
- Create: `modules/Academic/Tests/Feature/EnrollmentTest.php`
- Reuse: `tests/Pest.php`

**Step 1: Write the failing feature tests**

Create focused feature coverage that exercises the API contract before any controller or routes exist.

Minimum cases:

```php
it('requires authentication to list enrollments', function (): void {
    $this->getJson('/api/v1/academic/enrollments')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('forbids listing enrollments to students', function (): void {
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/academic/enrollments')
        ->assertForbidden()
        ->assertJsonPath('code', 'FORBIDDEN');
});

it('crea una inscripcion individual', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-IND-01');

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => (string) Str::uuid(),
        'status' => 'pending',
    ])->assertCreated()
        ->assertJsonPath('data.source', 'individual')
        ->assertJsonPath('data.status', 'pending');
});

it('lista enrollments filtrados por source y status', function (): void {
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments?source=bulk&status=active')
        ->assertOk();
});

it('activa, completa y cancela inscripciones', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    // crear enrollment via API o repositorio segun convenga al test
});
```

Also add tests for:

- bulk create success summary
- institutional create success
- `show`
- `422` invalid payloads
- `404` missing course/enrollment
- `409` duplicate active/pending enrollment
- `409` invalid lifecycle transition

Use helpers from `tests/Pest.php` plus repositories where needed to seed existing enrollments.

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php`

Expected: FAIL because routes, requests, controller, and permissions in routes do not exist yet.

**Step 3: Write minimal implementation support for the tests**

At this step only add any tiny test helper that is strictly necessary. Prefer keeping helpers in the test file itself, for example:

```php
function persistedEnrollmentForFeature(string $status = 'pending', string $source = 'individual'): Enrollment
{
    $course = createDraftCourseForPublishing('ENR-SEEDED-01');
    $enrollment = Enrollment::create(
        EnrollmentId::fromString((string) Str::uuid()),
        $course->id(),
        (string) Str::uuid(),
        null,
        EnrollmentStatus::from($status),
        EnrollmentSource::from($source),
    );

    app(EnrollmentRepository::class)->save($enrollment);

    return $enrollment;
}
```

Do not implement controller or routes yet; keep the suite red until the HTTP layer exists.

**Step 4: Run test to verify it still fails for the expected reason**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php`

Expected: still FAIL, now clearly due to missing HTTP implementation rather than missing test scaffolding.

**Step 5: Commit**

```bash
git add modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "test(academic): cover enrollment api scenarios"
```

### Task 3: Implement query routes and read endpoints

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/EnrollmentController.php`
- Create: `modules/Academic/Presentation/Http/Requests/ListEnrollmentsRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentTest.php`

**Step 1: Write the next failing read-side tests**

Narrow to the read scenarios if the full feature file is too noisy:

```php
it('lista enrollments con permission enrollments.view', function (): void {
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/academic/enrollments')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('muestra un enrollment especifico', function (): void {
    actingAsRole(Role::Teacher);
    $enrollment = persistedEnrollmentForFeature();

    $this->getJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $enrollment->id()->value());
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter=enrollment`

Expected: FAIL because the routes/controller/request do not exist yet.

**Step 3: Write minimal implementation**

Add the request, controller methods, and route group.

`ListEnrollmentsRequest` rules:

```php
return [
    'course_id' => ['nullable', 'string', 'uuid'],
    'user_id' => ['nullable', 'string', 'uuid'],
    'organization_id' => ['nullable', 'string', 'uuid'],
    'status' => ['nullable', Rule::in(['pending', 'active', 'completed', 'canceled'])],
    'source' => ['nullable', Rule::in(['individual', 'bulk', 'institutional'])],
];
```

`EnrollmentController` read methods:

```php
public function index(ListEnrollmentsRequest $request, QueryBus $queryBus): JsonResponse
{
    $result = $queryBus->ask(new ListEnrollmentsQuery(
        courseId: $request->validated('course_id'),
        userId: $request->validated('user_id'),
        organizationId: $request->validated('organization_id'),
        status: $request->validated('status'),
        source: $request->validated('source'),
    ));

    assert(is_array($result));

    return response()->json(['data' => array_map(
        static fn (EnrollmentListItemResponse $enrollment): array => $enrollment->toArray(),
        $result,
    )]);
}

public function show(string $enrollmentId, QueryBus $queryBus): JsonResponse
{
    $result = $queryBus->ask(new GetEnrollmentQuery(enrollmentId: $enrollmentId));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()]);
}
```

Add routes under:

```php
Route::middleware('permission:enrollments.view')->group(function (): void {
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/{enrollmentId}', [EnrollmentController::class, 'show'])
        ->whereUuid('enrollmentId')
        ->name('enrollments.show');
});
```

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter="lista|muestra|authentication|forbids"`

Expected: PASS for read/auth cases.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Http/Requests/ListEnrollmentsRequest.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "feat(academic): add enrollment query api"
```

### Task 4: Implement create endpoints and validation requests

**Files:**
- Modify: `modules/Academic/Presentation/Http/Controllers/EnrollmentController.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateEnrollmentRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateBulkEnrollmentsRequest.php`
- Create: `modules/Academic/Presentation/Http/Requests/CreateInstitutionalEnrollmentRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentTest.php`

**Step 1: Write the next failing create tests**

Add explicit create coverage:

```php
it('crea una inscripcion bulk', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-BULK-01');

    $this->postJson('/api/v1/academic/enrollments/bulk', [
        'course_id' => $course->id()->value(),
        'user_ids' => [(string) Str::uuid(), (string) Str::uuid()],
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.created', 2)
        ->assertJsonPath('data.failed', 0);
});

it('crea una inscripcion institucional', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    $course = createDraftCourseForPublishing('ENR-INS-01');

    $this->postJson('/api/v1/academic/enrollments/institutional', [
        'course_id' => $course->id()->value(),
        'user_id' => (string) Str::uuid(),
        'organization_id' => (string) Str::uuid(),
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.source', 'institutional');
});

it('valida payloads invalidos en create endpoints', function (): void {
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/academic/enrollments', [
        'course_id' => 'bad-id',
        'user_id' => 'bad-id',
        'status' => 'weird',
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});
```

Also include a duplicate conflict test that seeds an existing pending or active enrollment and expects `409` with `ENROLLMENT_ALREADY_EXISTS`.

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter="crea|valida|duplicate|bulk|institucional"`

Expected: FAIL because create requests/methods/routes do not exist.

**Step 3: Write minimal implementation**

Request rules:

```php
// CreateEnrollmentRequest
return [
    'course_id' => ['required', 'string', 'uuid'],
    'user_id' => ['required', 'string', 'uuid'],
    'status' => ['required', Rule::in(['pending', 'active', 'completed', 'canceled'])],
    'starts_at' => ['nullable', 'date'],
    'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
];
```

```php
// CreateBulkEnrollmentsRequest
return [
    'course_id' => ['required', 'string', 'uuid'],
    'user_ids' => ['required', 'array', 'min:1'],
    'user_ids.*' => ['required', 'string', 'uuid'],
    'status' => ['required', Rule::in(['pending', 'active', 'completed', 'canceled'])],
    'starts_at' => ['nullable', 'date'],
    'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
];
```

```php
// CreateInstitutionalEnrollmentRequest
return [
    'course_id' => ['required', 'string', 'uuid'],
    'user_id' => ['required', 'string', 'uuid'],
    'organization_id' => ['required', 'string', 'uuid'],
    'status' => ['required', Rule::in(['pending', 'active', 'completed', 'canceled'])],
    'starts_at' => ['nullable', 'date'],
    'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
];
```

Controller methods:

```php
public function store(CreateEnrollmentRequest $request, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new CreateEnrollmentCommand(
        courseId: (string) $request->validated('course_id'),
        userId: (string) $request->validated('user_id'),
        status: (string) $request->validated('status'),
        source: 'individual',
        startsAt: $request->validated('starts_at'),
        endsAt: $request->validated('ends_at'),
    ));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
}
```

```php
public function bulk(CreateBulkEnrollmentsRequest $request, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new CreateBulkEnrollmentsCommand(
        courseId: (string) $request->validated('course_id'),
        userIds: $request->validated('user_ids'),
        status: (string) $request->validated('status'),
        source: 'bulk',
        startsAt: $request->validated('starts_at'),
        endsAt: $request->validated('ends_at'),
    ));
    assert($result instanceof BulkEnrollmentResponse);

    return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
}
```

```php
public function institutional(CreateInstitutionalEnrollmentRequest $request, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new CreateInstitutionalEnrollmentCommand(
        courseId: (string) $request->validated('course_id'),
        userId: (string) $request->validated('user_id'),
        organizationId: (string) $request->validated('organization_id'),
        status: (string) $request->validated('status'),
        startsAt: $request->validated('starts_at'),
        endsAt: $request->validated('ends_at'),
    ));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
}
```

Routes:

```php
Route::middleware('permission:enrollments.manage')->group(function (): void {
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::post('/enrollments/bulk', [EnrollmentController::class, 'bulk'])->name('enrollments.bulk');
    Route::post('/enrollments/institutional', [EnrollmentController::class, 'institutional'])->name('enrollments.institutional');
});
```

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter="crea|valida|duplicate|bulk|institucional"`

Expected: PASS for create and validation scenarios.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Http/Requests/CreateEnrollmentRequest.php modules/Academic/Presentation/Http/Requests/CreateBulkEnrollmentsRequest.php modules/Academic/Presentation/Http/Requests/CreateInstitutionalEnrollmentRequest.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "feat(academic): add enrollment create api"
```

### Task 5: Implement lifecycle transition endpoints

**Files:**
- Modify: `modules/Academic/Presentation/Http/Controllers/EnrollmentController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Test: `modules/Academic/Tests/Feature/EnrollmentTest.php`

**Step 1: Write the failing lifecycle tests**

Add one test per transition and one invalid transition case:

```php
it('activa una inscripcion pendiente', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'pending');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/activate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('rechaza completar una inscripcion cancelada', function (): void {
    actingAsRole(Role::InstitutionalAdmin);
    $enrollment = persistedEnrollmentForFeature(status: 'canceled');

    $this->postJson("/api/v1/academic/enrollments/{$enrollment->id()->value()}/complete")
        ->assertStatus(409);
});
```

Include `cancel` and `complete` happy-path cases as well.

**Step 2: Run test to verify it fails**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter="activa|completa|cancela|rechaza completar"`

Expected: FAIL because lifecycle endpoints do not exist yet.

**Step 3: Write minimal implementation**

Add controller methods:

```php
public function activate(string $enrollmentId, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new ActivateEnrollmentCommand(enrollmentId: $enrollmentId));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()]);
}
```

```php
public function complete(string $enrollmentId, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new CompleteEnrollmentCommand(enrollmentId: $enrollmentId));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()]);
}
```

```php
public function cancel(string $enrollmentId, CommandBus $commandBus): JsonResponse
{
    $result = $commandBus->dispatch(new CancelEnrollmentCommand(enrollmentId: $enrollmentId));
    assert($result instanceof EnrollmentResponse);

    return response()->json(['data' => $result->toArray()]);
}
```

Add routes:

```php
Route::post('/enrollments/{enrollmentId}/activate', [EnrollmentController::class, 'activate'])
    ->whereUuid('enrollmentId')
    ->name('enrollments.activate');
Route::post('/enrollments/{enrollmentId}/complete', [EnrollmentController::class, 'complete'])
    ->whereUuid('enrollmentId')
    ->name('enrollments.complete');
Route::post('/enrollments/{enrollmentId}/cancel', [EnrollmentController::class, 'cancel'])
    ->whereUuid('enrollmentId')
    ->name('enrollments.cancel');
```

Keep all three inside the `permission:enrollments.manage` group.

**Step 4: Run test to verify it passes**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php --filter="activa|completa|cancela|rechaza completar"`

Expected: PASS.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php
git commit -m "feat(academic): add enrollment lifecycle api"
```

### Task 6: Run the full focused verification suite

**Files:**
- Verify: `modules/Academic/Tests/Feature/EnrollmentTest.php`
- Verify: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

**Step 1: Run the full enrollment-related test suite**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

Expected: PASS.

**Step 2: If a test fails, fix the smallest thing possible**

Typical fixes should be limited to one of these:

- request rule typo
- route missing `whereUuid()`
- wrong permission group in routes
- wrong response status (`200` vs `201`)
- wrong `source` mapping in controller

Do not refactor unrelated code while the suite is red.

**Step 3: Re-run the full suite**

Run: `php artisan test modules/Academic/Tests/Feature/EnrollmentTest.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

Expected: PASS with clean output.

**Step 4: Review changed files**

Run: `git diff -- modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Http/Requests modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

Expected: only enrollment HTTP API and permission changes.

**Step 5: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/EnrollmentController.php modules/Academic/Presentation/Http/Requests modules/Academic/Presentation/Routes/api.php modules/Academic/Tests/Feature/EnrollmentTest.php modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
git commit -m "feat(academic): add enrollment http api"
```
