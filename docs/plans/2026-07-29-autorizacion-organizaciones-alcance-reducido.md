# Autorización + Organizaciones (alcance reducido) — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add two new backend modules — `Authorization` (roles, permissions, permission-checking middleware) and `Organization` (organizations + campuses/sedes) — with a deliberately reduced scope, per `docs/plans/2026-07-29-consolidacion-autorizacion-organizaciones-design.md`.

**Architecture:** Follows `docs/engineering/ENG-003-estandar-modulos-backend.md` exactly (Domain/Application/Infrastructure/Presentation layers, `Modules\{Name}` namespace, CommandBus/QueryBus from Foundation, UUID primary keys, `Modules\Foundation\Domain\Exceptions\DomainException` for HTTP-mapped errors). `Organization` is built first (it has no dependencies). `Authorization`'s `RoleAssignment.organizationId` stores a plain nullable UUID column with **no DB foreign key** to `organizations` — this mirrors the existing precedent in `audit_logs.user_id` (see `database/migrations/2026_07_28_200257_create_audit_logs_table.php`), which is how this codebase keeps modules decoupled even at the schema level. Once both modules exist, the last tasks wire the new `permission` middleware onto the Organization module's write endpoints.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL (SQLite in-memory for tests), Pest, Sanctum for auth.

**IMPORTANT — run everything through Docker, not local PHP.** The host machine's `php` (8.2) cannot parse this codebase's PHP 8.3+ syntax (typed class constants like `private const int MAX_LENGTH`). Every command in this plan must be run as:

```bash
docker compose exec app <command>
```

If the containers aren't running yet: `docker compose up -d --build` from `edudrive-api/`.

---

## Conventions used throughout (read once, applies to every task)

- Every PHP file starts with `<?php` + blank line + `declare(strict_types=1);` + blank line.
- Value Objects: `final readonly class`, private constructor, static `fromString()` factory, `value()`, `equals()`, `__toString()`. Copy the exact shape of `modules/Academic/Domain/ValueObjects/CourseId.php` / `CourseTitle.php`.
- Aggregates: `final class` (not readonly, since internal state mutates), private constructor, static `create()` / `restore()` factories.
- Domain/Application exceptions that should map to a clean HTTP error **must** extend `Modules\Foundation\Domain\Exceptions\DomainException` (NOT PHP's built-in `\DomainException` — that's a pre-existing bug elsewhere in this codebase, already flagged separately, don't copy it).
- Eloquent models: `final class`, `$table`, `$primaryKey = 'id'`, `$incrementing = false`, `$keyType = 'string'`, `$fillable`, `casts()` method casting datetime columns to `immutable_datetime`.
- Commands implement `Modules\Foundation\Application\Commands\Command` (marker interface); Queries implement `Modules\Foundation\Application\Queries\Query`. Handlers are `final readonly class` with a `handle(...)` method, registered in the module's ServiceProvider via `MessageHandlerRegistry::register()`.
- Controllers are thin: validate via a `FormRequest`, build a Command/Query, dispatch via `CommandBus`/`QueryBus`, return `response()->json(['data' => ...])`.
- Routes: `Route::prefix('api/v1/{module}')->name('api.v1.{module}.')->group(...)`, every route named.
- Every new module needs a status endpoint (`GET /api/v1/{module}/status`) per ENG-003 §18, registered in `bootstrap/providers.php`.
- Test file naming/location: `modules/{Module}/Tests/{Unit|Feature|Integration}/...`. Plain test files (no helper classes) don't declare a namespace — copy the style of `CourseCodeTest.php`. Test files that define extra top-level classes (not anonymous classes) declare a namespace under `Modules\{Module}\Tests\...` — copy the style of `LaravelCommandBusTest.php`. Anonymous classes (`new class implements X {}`) never need a namespace.
- Run a single test file with: `docker compose exec app php artisan test path/to/File.php`.
- After **every** task: `docker compose exec app composer quality` must pass before committing.

---

### Task 1: Scaffold the Organization module (status endpoint)

**Files:**
- Create: `modules/Organization/Presentation/Http/Controllers/OrganizationStatusController.php`
- Create: `modules/Organization/Presentation/Routes/api.php`
- Create: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Create: `modules/Organization/Tests/Feature/OrganizationStatusTest.php`
- Modify: `bootstrap/providers.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de organizaciones está disponible', function (): void {
    getJson('/api/v1/organizations/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Organization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
```

**Step 2: Run test to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/OrganizationStatusTest.php`
Expected: FAIL (route not found / 404).

**Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class OrganizationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Organization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}
```

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');
    });
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

Modify `bootstrap/providers.php` to the full new content:

```php
<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Academic\Infrastructure\Providers\AcademicServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Foundation\Providers\FoundationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Organization\Infrastructure\Providers\OrganizationServiceProvider;

return [
    AppServiceProvider::class,
    FoundationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    AcademicServiceProvider::class,
    OrganizationServiceProvider::class,
];
```

Note: `loadMigrationsFrom` points at a `Persistence/Migrations` directory that doesn't exist yet — that's fine, Laravel silently ignores a missing migrations directory.

**Step 4: Run test to verify it passes**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/OrganizationStatusTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add bootstrap/providers.php modules/Organization
git commit -m "feat(organization): scaffold module with status endpoint"
```

---

### Task 2: Organization value objects (`OrganizationId`, `OrganizationName`)

**Files:**
- Create: `modules/Organization/Domain/ValueObjects/OrganizationId.php`
- Create: `modules/Organization/Domain/ValueObjects/OrganizationName.php`
- Test: `modules/Organization/Tests/Unit/Domain/ValueObjects/OrganizationIdTest.php`
- Test: `modules/Organization/Tests/Unit/Domain/ValueObjects/OrganizationNameTest.php`

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObjects\OrganizationId;

it('crea un identificador de organización válido', function (): void {
    $id = OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0');

    expect($id->value())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0');
});

it('rechaza un identificador de organización inválido', function (): void {
    OrganizationId::fromString('identificador-invalido');
})->throws(
    InvalidArgumentException::class,
    'El identificador de la organización debe ser un UUID válido.',
);
```

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObjects\OrganizationName;

it('normaliza el nombre de una organización', function (): void {
    $name = OrganizationName::fromString('  Escuela de Manejo EDUDRIVE  ');

    expect($name->value())->toBe('Escuela de Manejo EDUDRIVE');
});

it('rechaza un nombre vacío', function (): void {
    OrganizationName::fromString('   ');
})->throws(
    InvalidArgumentException::class,
    'El nombre de la organización no puede estar vacío.',
);

it('rechaza un nombre demasiado largo', function (): void {
    OrganizationName::fromString(str_repeat('a', 181));
})->throws(
    InvalidArgumentException::class,
    'El nombre de la organización no puede superar 180 caracteres.',
);
```

**Step 2: Run to verify both fail**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/ValueObjects`
Expected: FAIL (classes don't exist).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class OrganizationId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (! self::isValidUuid($value)) {
            throw new InvalidArgumentException('El identificador de la organización debe ser un UUID válido.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function isValidUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $value,
        ) === 1;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class OrganizationName
{
    private const int MAX_LENGTH = 180;

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('El nombre de la organización no puede estar vacío.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('El nombre de la organización no puede superar %d caracteres.', self::MAX_LENGTH),
            );
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/ValueObjects`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add OrganizationId and OrganizationName value objects"
```

---

### Task 3: `OrganizationType` enum + `Campus` entity

**Files:**
- Create: `modules/Organization/Domain/Enums/OrganizationType.php`
- Create: `modules/Organization/Domain/Entities/Campus.php`
- Test: `modules/Organization/Tests/Unit/Domain/Entities/CampusTest.php`

**Step 1: Write the failing test** (only `Campus` needs one — the enum is a plain data type, same as `CourseStatus` which has no dedicated test)

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\Entities\Campus;

it('crea una sede con nombre normalizado', function (): void {
    $campus = Campus::create(
        id: '01981a64-8300-7b1d-b442-764ea7f915c0',
        name: '  Sede Cabo Velas  ',
    );

    expect($campus->id())->toBe('01981a64-8300-7b1d-b442-764ea7f915c0')
        ->and($campus->name())->toBe('Sede Cabo Velas');
});

it('rechaza una sede sin nombre', function (): void {
    Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c0', name: '   ');
})->throws(
    InvalidArgumentException::class,
    'El nombre de la sede no puede estar vacío.',
);
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/Entities/CampusTest.php`
Expected: FAIL (class doesn't exist).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Enums;

enum OrganizationType: string
{
    case EducationalCenter = 'educational_center';
    case DrivingSchool = 'driving_school';
    case Company = 'company';
    case PublicInstitution = 'public_institution';
    case Other = 'other';
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Entities;

use InvalidArgumentException;

final class Campus
{
    private function __construct(
        private readonly string $id,
        private string $name,
    ) {}

    public static function create(string $id, string $name): self
    {
        return new self(
            id: $id,
            name: self::normalizeName($name),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    private static function normalizeName(string $name): string
    {
        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new InvalidArgumentException('El nombre de la sede no puede estar vacío.');
        }

        if (mb_strlen($normalizedName) > 180) {
            throw new InvalidArgumentException('El nombre de la sede no puede superar 180 caracteres.');
        }

        return $normalizedName;
    }
}
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/Entities/CampusTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add OrganizationType enum and Campus entity"
```

---

### Task 4: `Organization` aggregate

**Files:**
- Create: `modules/Organization/Domain/Aggregates/Organization.php`
- Test: `modules/Organization/Tests/Unit/Domain/Aggregates/OrganizationTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

it('crea una organización sin sedes', function (): void {
    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    expect($organization->name()->value())->toBe('Escuela de Manejo EDUDRIVE')
        ->and($organization->type())->toBe(OrganizationType::DrivingSchool)
        ->and($organization->campuses())->toBe([]);
});

it('permite agregar sedes a una organización', function (): void {
    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    $campus = Campus::create(
        id: '01981a64-8300-7b1d-b442-764ea7f915c1',
        name: 'Sede Cabo Velas',
    );

    $organization->addCampus($campus);

    expect($organization->campuses())->toHaveCount(1)
        ->and($organization->campuses()[0]->name())->toBe('Sede Cabo Velas');
});
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/Aggregates/OrganizationTest.php`
Expected: FAIL (class doesn't exist).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Aggregates;

use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

final class Organization
{
    /**
     * @var list<Campus>
     */
    private array $campuses;

    /**
     * @param  list<Campus>  $campuses
     */
    private function __construct(
        private readonly OrganizationId $id,
        private OrganizationName $name,
        private readonly OrganizationType $type,
        array $campuses = [],
    ) {
        $this->campuses = array_values($campuses);
    }

    public static function create(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationType $type,
    ): self {
        return new self(
            id: $id,
            name: $name,
            type: $type,
            campuses: [],
        );
    }

    /**
     * @param  list<Campus>  $campuses
     */
    public static function restore(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationType $type,
        array $campuses,
    ): self {
        return new self(
            id: $id,
            name: $name,
            type: $type,
            campuses: $campuses,
        );
    }

    public function addCampus(Campus $campus): void
    {
        $this->campuses[] = $campus;
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function name(): OrganizationName
    {
        return $this->name;
    }

    public function type(): OrganizationType
    {
        return $this->type;
    }

    /**
     * @return list<Campus>
     */
    public function campuses(): array
    {
        return $this->campuses;
    }
}
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Unit/Domain/Aggregates/OrganizationTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add Organization aggregate"
```

---

### Task 5: `OrganizationRepository` interface + migrations

**Files:**
- Create: `modules/Organization/Domain/Repositories/OrganizationRepository.php`
- Create: `modules/Organization/Infrastructure/Persistence/Migrations/2026_07_29_000002_create_organizations_table.php`
- Create: `modules/Organization/Infrastructure/Persistence/Migrations/2026_07_29_000003_create_organization_campuses_table.php`

No test in this task (a pure interface + migrations aren't independently testable — they get exercised in Task 6). If you're implementing this on a different day, bump the migration filename timestamps accordingly (they only need to sort after `2026_07_29_000001_create_academic_courses_table.php` and before each other).

**Step 1: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Repositories;

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    public function findById(OrganizationId $id): ?Organization;

    /**
     * @return list<Organization>
     */
    public function all(): array;
}
```

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 180);
            $table->string('type', 30)->index();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
```

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_campuses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('organization_id')->index();
            $table->string('name', 180);

            $table->timestampsTz();

            $table
                ->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_campuses');
    }
};
```

Note: the FK from `organization_campuses` to `organizations` is fine — both tables belong to the **same** module. Cross-module references (like `Authorization` → `Organization` later) do NOT get a DB-level FK, per the convention note at the top of this plan.

**Step 2: Verify migrations run**

Run: `docker compose exec app php artisan migrate:status`
Expected: the two new migrations listed as "Pending".

**Step 3: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add repository contract and persistence migrations"
```

---

### Task 6: Eloquent models + `EloquentOrganizationRepository`

**Files:**
- Create: `modules/Organization/Infrastructure/Persistence/Eloquent/Models/OrganizationModel.php`
- Create: `modules/Organization/Infrastructure/Persistence/Eloquent/Models/CampusModel.php`
- Create: `modules/Organization/Infrastructure/Persistence/Eloquent/Repositories/EloquentOrganizationRepository.php`
- Modify: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Test: `modules/Organization/Tests/Integration/EloquentOrganizationRepositoryTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;
use Tests\TestCase;

it('guarda y recupera una organización con sus sedes', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(OrganizationRepository::class);

    $organization = Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    );

    $organization->addCampus(
        Campus::create(id: '01981a64-8300-7b1d-b442-764ea7f915c1', name: 'Sede Cabo Velas'),
    );

    $repository->save($organization);

    $persisted = $repository->findById($organization->id());

    expect($persisted)->not->toBeNull()
        ->and($persisted?->name()->value())->toBe('Escuela de Manejo EDUDRIVE')
        ->and($persisted?->campuses())->toHaveCount(1)
        ->and($persisted?->campuses()[0]->name())->toBe('Sede Cabo Velas');
});

it('lista todas las organizaciones', function (): void {
    /** @var TestCase $this */
    $repository = $this->app->make(OrganizationRepository::class);

    $repository->save(Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c2'),
        name: OrganizationName::fromString('Centro Educativo A'),
        type: OrganizationType::EducationalCenter,
    ));

    $repository->save(Organization::create(
        id: OrganizationId::fromString('01981a64-8300-7b1d-b442-764ea7f915c3'),
        name: OrganizationName::fromString('Centro Educativo B'),
        type: OrganizationType::EducationalCenter,
    ));

    expect($repository->all())->toHaveCount(2);
});
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Integration/EloquentOrganizationRepositoryTest.php`
Expected: FAIL (`OrganizationRepository` has no bound implementation).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OrganizationModel extends Model
{
    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function campuses(): HasMany
    {
        return $this->hasMany(CampusModel::class, 'organization_id');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CampusModel extends Model
{
    protected $table = 'organization_campuses';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'organization_id',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\CampusModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

final class EloquentOrganizationRepository implements OrganizationRepository
{
    public function save(Organization $organization): void
    {
        OrganizationModel::query()->updateOrCreate(
            ['id' => $organization->id()->value()],
            [
                'name' => $organization->name()->value(),
                'type' => $organization->type()->value,
            ],
        );

        CampusModel::query()
            ->where('organization_id', $organization->id()->value())
            ->delete();

        foreach ($organization->campuses() as $campus) {
            CampusModel::query()->create([
                'id' => $campus->id(),
                'organization_id' => $organization->id()->value(),
                'name' => $campus->name(),
            ]);
        }
    }

    public function findById(OrganizationId $id): ?Organization
    {
        $model = OrganizationModel::query()->find($id->value());

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    /**
     * @return list<Organization>
     */
    public function all(): array
    {
        $organizations = OrganizationModel::query()
            ->orderBy('created_at')
            ->get()
            ->map(
                fn (OrganizationModel $model): Organization => $this->toDomain($model),
            )
            ->all();

        return array_values($organizations);
    }

    private function toDomain(OrganizationModel $model): Organization
    {
        $campuses = CampusModel::query()
            ->where('organization_id', $model->getAttribute('id'))
            ->orderBy('created_at')
            ->get()
            ->map(
                static fn (CampusModel $campusModel): Campus => Campus::create(
                    id: (string) $campusModel->getAttribute('id'),
                    name: (string) $campusModel->getAttribute('name'),
                ),
            )
            ->all();

        return Organization::restore(
            id: OrganizationId::fromString((string) $model->getAttribute('id')),
            name: OrganizationName::fromString((string) $model->getAttribute('name')),
            type: OrganizationType::from((string) $model->getAttribute('type')),
            campuses: array_values($campuses),
        );
    }
}
```

Modify `OrganizationServiceProvider` (full new content):

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationRepository;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrganizationRepository::class,
            EloquentOrganizationRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Integration/EloquentOrganizationRepositoryTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add Eloquent persistence for Organization aggregate"
```

---

### Task 7: `CreateOrganization` command (end-to-end: command, handler, response, request, controller, route, feature test)

**Files:**
- Create: `modules/Organization/Application/Commands/CreateOrganizationCommand.php`
- Create: `modules/Organization/Application/Responses/CreateOrganizationResponse.php`
- Create: `modules/Organization/Application/UseCases/CreateOrganizationHandler.php`
- Create: `modules/Organization/Presentation/Http/Requests/CreateOrganizationRequest.php`
- Create: `modules/Organization/Presentation/Http/Controllers/OrganizationController.php`
- Modify: `modules/Organization/Presentation/Routes/api.php`
- Modify: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Test: `modules/Organization/Tests/Feature/CreateOrganizationTest.php`

This is the first authenticated endpoint in the whole codebase to get a Pest feature test — there's no existing example, so it introduces the `Laravel\Sanctum\Sanctum::actingAs()` helper pattern (standard Sanctum testing API, already available since `laravel/sanctum` is installed).

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function actingAsAuthenticatedUser(): UserModel
{
    /** @var Tests\TestCase $this */
    $repository = test()->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    Sanctum::actingAs($model);

    return $model;
}

it('crea una organización cuando el usuario está autenticado', function (): void {
    actingAsAuthenticatedUser();

    $response = postJson('/api/v1/organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Escuela de Manejo EDUDRIVE')
        ->assertJsonPath('data.type', 'driving_school')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'type'],
        ]);

    assertDatabaseHas('organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/organizations', [
        'name' => 'Sin autenticación',
        'type' => 'company',
    ])->assertUnauthorized();
});

it('rechaza datos obligatorios faltantes', function (): void {
    actingAsAuthenticatedUser();

    postJson('/api/v1/organizations', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type']);
});

it('rechaza un tipo de organización inválido', function (): void {
    actingAsAuthenticatedUser();

    postJson('/api/v1/organizations', [
        'name' => 'Organización X',
        'type' => 'not-a-real-type',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/CreateOrganizationTest.php`
Expected: FAIL (route doesn't exist yet).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateOrganizationCommand implements Command
{
    public function __construct(
        public string $name,
        public string $type,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Aggregates\Organization;

final readonly class CreateOrganizationResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $type,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id()->value(),
            name: $organization->name()->value(),
            type: $organization->type()->value,
        );
    }

    /**
     * @return array{id: string, name: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

final readonly class CreateOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(
        CreateOrganizationCommand $command,
    ): CreateOrganizationResponse {
        $organization = Organization::create(
            id: OrganizationId::fromString((string) Str::uuid()),
            name: OrganizationName::fromString($command->name),
            type: OrganizationType::from($command->type),
        );

        $this->organizations->save($organization);

        return CreateOrganizationResponse::fromOrganization($organization);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Organization\Domain\Enums\OrganizationType;

final class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:180',
            ],
            'type' => [
                'required',
                'string',
                new Enum(OrganizationType::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la organización es obligatorio.',
            'name.max' => 'El nombre no puede superar 180 caracteres.',
            'type.required' => 'El tipo de organización es obligatorio.',
            'type.enum' => 'El tipo de organización no es válido.',
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationController
{
    public function store(
        CreateOrganizationRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new CreateOrganizationCommand(
                name: (string) $validated['name'],
                type: (string) $validated['type'],
            ),
        );

        assert($result instanceof CreateOrganizationResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
```

Modify `modules/Organization/Presentation/Routes/api.php` (full new content):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationController;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/', [OrganizationController::class, 'store'])
                ->name('store');
        });
    });
```

Modify `OrganizationServiceProvider` (full new content):

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\UseCases\CreateOrganizationHandler;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrganizationRepository;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrganizationRepository::class,
            EloquentOrganizationRepository::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(
            CreateOrganizationCommand::class,
            CreateOrganizationHandler::class,
        );

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/CreateOrganizationTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add CreateOrganization command and endpoint"
```

---

### Task 8: `AddCampus` command (end-to-end)

**Files:**
- Create: `modules/Organization/Application/Commands/AddCampusCommand.php`
- Create: `modules/Organization/Application/Exceptions/OrganizationNotFound.php`
- Create: `modules/Organization/Application/Responses/AddCampusResponse.php`
- Create: `modules/Organization/Application/UseCases/AddCampusHandler.php`
- Create: `modules/Organization/Presentation/Http/Requests/AddCampusRequest.php`
- Modify: `modules/Organization/Presentation/Http/Controllers/OrganizationController.php`
- Modify: `modules/Organization/Presentation/Routes/api.php`
- Modify: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Test: `modules/Organization/Tests/Feature/AddCampusTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function anAuthenticatedUserForAddCampusTest(): void
{
    /** @var Tests\TestCase $this */
    $repository = test()->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));
}

it('agrega una sede a una organización existente', function (): void {
    /** @var Tests\TestCase $this */
    $organizations = test()->app->make(OrganizationRepository::class);

    $organizationId = OrganizationId::fromString((string) Illuminate\Support\Str::uuid());

    $organizations->save(Organization::create(
        id: $organizationId,
        name: OrganizationName::fromString('Escuela de Manejo EDUDRIVE'),
        type: OrganizationType::DrivingSchool,
    ));

    anAuthenticatedUserForAddCampusTest();

    postJson("/api/v1/organizations/{$organizationId->value()}/campuses", [
        'name' => 'Sede Cabo Velas',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sede Cabo Velas');

    assertDatabaseHas('organization_campuses', [
        'organization_id' => $organizationId->value(),
        'name' => 'Sede Cabo Velas',
    ]);
});

it('devuelve 404 al agregar una sede a una organización inexistente', function (): void {
    anAuthenticatedUserForAddCampusTest();

    postJson('/api/v1/organizations/'.((string) Illuminate\Support\Str::uuid()).'/campuses', [
        'name' => 'Sede Fantasma',
    ])->assertNotFound();
});
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/AddCampusTest.php`
Expected: FAIL (route doesn't exist).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class AddCampusCommand implements Command
{
    public function __construct(
        public string $organizationId,
        public string $name,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class OrganizationNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe una organización con el identificador %s.', $id),
            errorCode: 'ORGANIZATION_NOT_FOUND',
            statusCode: 404,
        );
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Entities\Campus;

final readonly class AddCampusResponse
{
    private function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromCampus(Campus $campus): self
    {
        return new self(
            id: $campus->id(),
            name: $campus->name(),
        );
    }

    /**
     * @return array{id: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Organization\Application\Commands\AddCampusCommand;
use Modules\Organization\Application\Exceptions\OrganizationNotFound;
use Modules\Organization\Application\Responses\AddCampusResponse;
use Modules\Organization\Domain\Entities\Campus;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class AddCampusHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    public function handle(
        AddCampusCommand $command,
    ): AddCampusResponse {
        $organizationId = OrganizationId::fromString($command->organizationId);
        $organization = $this->organizations->findById($organizationId);

        if ($organization === null) {
            throw OrganizationNotFound::withId($command->organizationId);
        }

        $campus = Campus::create(
            id: (string) Str::uuid(),
            name: $command->name,
        );

        $organization->addCampus($campus);

        $this->organizations->save($organization);

        return AddCampusResponse::fromCampus($campus);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddCampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:180',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la sede es obligatorio.',
            'name.max' => 'El nombre no puede superar 180 caracteres.',
        ];
    }
}
```

Modify `OrganizationController` (full new content):

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Organization\Application\Commands\AddCampusCommand;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Responses\AddCampusResponse;
use Modules\Organization\Application\Responses\CreateOrganizationResponse;
use Modules\Organization\Presentation\Http\Requests\AddCampusRequest;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationController
{
    public function store(
        CreateOrganizationRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new CreateOrganizationCommand(
                name: (string) $validated['name'],
                type: (string) $validated['type'],
            ),
        );

        assert($result instanceof CreateOrganizationResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }

    public function addCampus(
        string $organizationId,
        AddCampusRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new AddCampusCommand(
                organizationId: $organizationId,
                name: (string) $validated['name'],
            ),
        );

        assert($result instanceof AddCampusResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
```

Modify `modules/Organization/Presentation/Routes/api.php` (full new content):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationController;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/', [OrganizationController::class, 'store'])
                ->name('store');

            Route::post('/{organizationId}/campuses', [OrganizationController::class, 'addCampus'])
                ->whereUuid('organizationId')
                ->name('campuses.store');
        });
    });
```

Modify `OrganizationServiceProvider` — add the registration inside `boot()`:

```php
        $registry->register(
            AddCampusCommand::class,
            AddCampusHandler::class,
        );
```

(with the corresponding `use Modules\Organization\Application\Commands\AddCampusCommand;` and `use Modules\Organization\Application\UseCases\AddCampusHandler;` added at the top, right after `CreateOrganizationCommand` and its handler).

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/AddCampusTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): add AddCampus command and endpoint"
```

---

### Task 9: `ListOrganizations` query (end-to-end)

**Files:**
- Create: `modules/Organization/Application/Queries/ListOrganizationsQuery.php`
- Create: `modules/Organization/Application/Responses/OrganizationListItemResponse.php`
- Create: `modules/Organization/Application/UseCases/ListOrganizationsHandler.php`
- Modify: `modules/Organization/Presentation/Http/Controllers/OrganizationController.php`
- Modify: `modules/Organization/Presentation/Routes/api.php`
- Modify: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Test: `modules/Organization/Tests/Feature/ListOrganizationsTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use function Pest\Laravel\getJson;

it('lista las organizaciones existentes', function (): void {
    /** @var Tests\TestCase $this */
    $organizations = test()->app->make(OrganizationRepository::class);

    $organizations->save(Organization::create(
        id: OrganizationId::fromString((string) Illuminate\Support\Str::uuid()),
        name: OrganizationName::fromString('Centro Educativo EDUDRIVE'),
        type: OrganizationType::EducationalCenter,
    ));

    getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Centro Educativo EDUDRIVE')
        ->assertJsonPath('data.0.campuses', []);
});
```

**Step 2: Run to verify it fails**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/ListOrganizationsTest.php`
Expected: FAIL (route doesn't exist).

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListOrganizationsQuery implements Query {}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Aggregates\Organization;

final readonly class OrganizationListItemResponse
{
    /**
     * @param  list<array{id: string, name: string}>  $campuses
     */
    private function __construct(
        public string $id,
        public string $name,
        public string $type,
        public array $campuses,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id()->value(),
            name: $organization->name()->value(),
            type: $organization->type()->value,
            campuses: array_map(
                static fn ($campus): array => [
                    'id' => $campus->id(),
                    'name' => $campus->name(),
                ],
                $organization->campuses(),
            ),
        );
    }

    /**
     * @return array{id: string, name: string, type: string, campuses: list<array{id: string, name: string}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'campuses' => $this->campuses,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Application\UseCases;

use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Repositories\OrganizationRepository;

final readonly class ListOrganizationsHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {}

    /**
     * @return list<OrganizationListItemResponse>
     */
    public function handle(ListOrganizationsQuery $query): array
    {
        return array_map(
            static fn (Organization $organization): OrganizationListItemResponse => OrganizationListItemResponse::fromOrganization($organization),
            $this->organizations->all(),
        );
    }
}
```

Modify `OrganizationController` — add this method (and the corresponding `use` statements for `QueryBus`, `ListOrganizationsQuery`, `OrganizationListItemResponse`):

```php
    public function index(
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new ListOrganizationsQuery,
        );

        assert(is_array($result));

        /** @var list<OrganizationListItemResponse> $result */
        $data = array_map(
            static fn (OrganizationListItemResponse $organization): array => $organization->toArray(),
            $result,
        );

        return response()->json(['data' => $data]);
    }
```

Modify `modules/Organization/Presentation/Routes/api.php` — add, inside the `auth:sanctum` group, before the `store` route:

```php
            Route::get('/', [OrganizationController::class, 'index'])
                ->name('index');
```

Modify `OrganizationServiceProvider` — add the registration inside `boot()`:

```php
        $registry->register(
            ListOrganizationsQuery::class,
            ListOrganizationsHandler::class,
        );
```

**Step 4: Run to verify pass**

Run: `docker compose exec app php artisan test modules/Organization/Tests/Feature/ListOrganizationsTest.php`
Expected: PASS

**Step 5: Run the whole Organization test suite, then commit**

Run: `docker compose exec app php artisan test modules/Organization`
Expected: all PASS

```bash
git add modules/Organization
git commit -m "feat(organization): add ListOrganizations query and endpoint"
```

---

### Task 10: Scaffold the Authorization module (status endpoint)

**Files:**
- Create: `modules/Authorization/Presentation/Http/Controllers/AuthorizationStatusController.php`
- Create: `modules/Authorization/Presentation/Routes/api.php`
- Create: `modules/Authorization/Infrastructure/Providers/AuthorizationServiceProvider.php`
- Create: `modules/Authorization/Tests/Feature/AuthorizationStatusTest.php`
- Modify: `bootstrap/providers.php`

Identical shape to Task 1. **Step 1 (test):**

```php
<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de autorización está disponible', function (): void {
    getJson('/api/v1/authorization/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Authorization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
```

**Step 2:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Feature/AuthorizationStatusTest.php` → FAIL.

**Step 3 (implementation):**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AuthorizationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Authorization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}
```

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Presentation\Http\Controllers\AuthorizationStatusController;

Route::prefix('api/v1/authorization')
    ->name('api.v1.authorization.')
    ->group(function (): void {
        Route::get('/status', AuthorizationStatusController::class)
            ->name('status');
    });
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

Modify `bootstrap/providers.php` (full new content):

```php
<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Academic\Infrastructure\Providers\AcademicServiceProvider;
use Modules\Audit\Infrastructure\Providers\AuditServiceProvider;
use Modules\Authorization\Infrastructure\Providers\AuthorizationServiceProvider;
use Modules\Foundation\Providers\FoundationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Organization\Infrastructure\Providers\OrganizationServiceProvider;

return [
    AppServiceProvider::class,
    FoundationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    AcademicServiceProvider::class,
    OrganizationServiceProvider::class,
    AuthorizationServiceProvider::class,
];
```

**Step 4:** Run the test again → PASS.

**Step 5: Commit**

```bash
git add bootstrap/providers.php modules/Authorization
git commit -m "feat(authorization): scaffold module with status endpoint"
```

---

### Task 11: `Role` + `Permission` enums, `RolePermissions` domain service

**Files:**
- Create: `modules/Authorization/Domain/Enums/Role.php`
- Create: `modules/Authorization/Domain/Enums/Permission.php`
- Create: `modules/Authorization/Domain/Services/RolePermissions.php`
- Test: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`

This is the reduced catalog agreed in the design doc: 4 roles, 3 permissions. `institutional_admin`, `teacher` and `student` currently only grant `organizations.view` — organization-scoped permissions (e.g. an institutional admin managing only their own organization) are explicitly deferred.

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Services\RolePermissions;

it('otorga todos los permisos definidos al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ManageRoleAssignments))->toBeTrue();
});

it('solo otorga permisos de visualización a administradores institucionales, docentes y estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageOrganizations))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageRoleAssignments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageOrganizations))->toBeFalse();
});
```

**Step 2:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php` → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case InstitutionalAdmin = 'institutional_admin';
    case Teacher = 'teacher';
    case Student = 'student';
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Permission: string
{
    case ManageOrganizations = 'organizations.manage';
    case ViewOrganizations = 'organizations.view';
    case ManageRoleAssignments = 'roles.manage';
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Services;

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;

final class RolePermissions
{
    /**
     * @var array<string, list<Permission>>
     */
    private const array MAP = [
        'super_admin' => [
            Permission::ManageOrganizations,
            Permission::ViewOrganizations,
            Permission::ManageRoleAssignments,
        ],
        'institutional_admin' => [
            Permission::ViewOrganizations,
        ],
        'teacher' => [
            Permission::ViewOrganizations,
        ],
        'student' => [
            Permission::ViewOrganizations,
        ],
    ];

    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::MAP[$role->value], true);
    }
}
```

**Step 4:** Run again → PASS.

**Step 5: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add Role/Permission enums and RolePermissions service"
```

---

### Task 12: `RoleAssignment` entity

**Files:**
- Create: `modules/Authorization/Domain/Entities/RoleAssignment.php`
- Test: `modules/Authorization/Tests/Unit/Domain/Entities/RoleAssignmentTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;

it('crea una asignación de rol con fecha por defecto', function (): void {
    $assignment = RoleAssignment::assign(
        id: '01981a64-8300-7b1d-b442-764ea7f91600',
        userId: '01981a64-8300-7b1d-b442-764ea7f91601',
        role: Role::Teacher,
        organizationId: null,
    );

    expect($assignment->role())->toBe(Role::Teacher)
        ->and($assignment->userId())->toBe('01981a64-8300-7b1d-b442-764ea7f91601')
        ->and($assignment->organizationId())->toBeNull()
        ->and($assignment->assignedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('acepta una organización asociada a la asignación', function (): void {
    $assignment = RoleAssignment::assign(
        id: '01981a64-8300-7b1d-b442-764ea7f91602',
        userId: '01981a64-8300-7b1d-b442-764ea7f91601',
        role: Role::InstitutionalAdmin,
        organizationId: '01981a64-8300-7b1d-b442-764ea7f91603',
    );

    expect($assignment->organizationId())->toBe('01981a64-8300-7b1d-b442-764ea7f91603');
});
```

**Step 2:** Run → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Entities;

use DateTimeImmutable;
use Modules\Authorization\Domain\Enums\Role;

final class RoleAssignment
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly Role $role,
        private readonly ?string $organizationId,
        private readonly DateTimeImmutable $assignedAt,
    ) {}

    public static function assign(
        string $id,
        string $userId,
        Role $role,
        ?string $organizationId,
        ?DateTimeImmutable $assignedAt = null,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            role: $role,
            organizationId: $organizationId,
            assignedAt: $assignedAt ?? new DateTimeImmutable,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
```

**Step 4:** Run again → PASS.

**Step 5: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add RoleAssignment entity"
```

---

### Task 13: `RoleAssignmentRepository` interface + migration

**Files:**
- Create: `modules/Authorization/Domain/Repositories/RoleAssignmentRepository.php`
- Create: `modules/Authorization/Infrastructure/Persistence/Migrations/2026_07_29_000004_create_authorization_role_assignments_table.php`

No test (same reasoning as Task 5).

**Step 1: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Repositories;

use Modules\Authorization\Domain\Entities\RoleAssignment;

interface RoleAssignmentRepository
{
    public function save(RoleAssignment $assignment): void;

    /**
     * @return list<RoleAssignment>
     */
    public function findByUserId(string $userId): array;
}
```

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_role_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->string('role', 30)->index();
            $table->uuid('organization_id')->nullable()->index();

            $table->timestampTz('assigned_at')->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_role_assignments');
    }
};
```

Note: `user_id` and `organization_id` are plain indexed UUID columns with **no foreign key** — they reference `Identity` and `Organization` respectively, and per this codebase's convention (see `audit_logs.user_id`), cross-module references never get a DB-level FK.

**Step 2:** `docker compose exec app php artisan migrate:status` → new migration listed as Pending.

**Step 3: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add RoleAssignment repository contract and migration"
```

---

### Task 14: `RoleAssignmentModel` + `EloquentRoleAssignmentRepository`

**Files:**
- Create: `modules/Authorization/Infrastructure/Persistence/Eloquent/Models/RoleAssignmentModel.php`
- Create: `modules/Authorization/Infrastructure/Persistence/Eloquent/Repositories/EloquentRoleAssignmentRepository.php`
- Modify: `modules/Authorization/Infrastructure/Providers/AuthorizationServiceProvider.php`
- Test: `modules/Authorization/Tests/Integration/EloquentRoleAssignmentRepositoryTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;

it('guarda y recupera las asignaciones de rol de un usuario', function (): void {
    /** @var Tests\TestCase $this */
    $repository = $this->app->make(RoleAssignmentRepository::class);

    $userId = (string) Illuminate\Support\Str::uuid();

    $repository->save(RoleAssignment::assign(
        id: (string) Illuminate\Support\Str::uuid(),
        userId: $userId,
        role: Role::Teacher,
        organizationId: null,
    ));

    $repository->save(RoleAssignment::assign(
        id: (string) Illuminate\Support\Str::uuid(),
        userId: $userId,
        role: Role::Student,
        organizationId: (string) Illuminate\Support\Str::uuid(),
    ));

    $assignments = $repository->findByUserId($userId);

    expect($assignments)->toHaveCount(2)
        ->and($assignments[0]->role())->toBe(Role::Teacher)
        ->and($assignments[1]->role())->toBe(Role::Student);
});

it('no devuelve asignaciones de otros usuarios', function (): void {
    /** @var Tests\TestCase $this */
    $repository = $this->app->make(RoleAssignmentRepository::class);

    $repository->save(RoleAssignment::assign(
        id: (string) Illuminate\Support\Str::uuid(),
        userId: (string) Illuminate\Support\Str::uuid(),
        role: Role::Student,
        organizationId: null,
    ));

    expect($repository->findByUserId((string) Illuminate\Support\Str::uuid()))->toBe([]);
});
```

**Step 2:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Integration/EloquentRoleAssignmentRepositoryTest.php` → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $role
 * @property string|null $organization_id
 * @property Carbon $assigned_at
 */
final class RoleAssignmentModel extends Model
{
    protected $table = 'authorization_role_assignments';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'role',
        'organization_id',
        'assigned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;

final class EloquentRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function save(RoleAssignment $assignment): void
    {
        RoleAssignmentModel::query()->updateOrCreate(
            ['id' => $assignment->id()],
            [
                'user_id' => $assignment->userId(),
                'role' => $assignment->role()->value,
                'organization_id' => $assignment->organizationId(),
                'assigned_at' => $assignment->assignedAt(),
            ],
        );
    }

    /**
     * @return list<RoleAssignment>
     */
    public function findByUserId(string $userId): array
    {
        $assignments = RoleAssignmentModel::query()
            ->where('user_id', $userId)
            ->orderBy('assigned_at')
            ->get()
            ->map(
                static fn (RoleAssignmentModel $model): RoleAssignment => RoleAssignment::assign(
                    id: (string) $model->getAttribute('id'),
                    userId: (string) $model->getAttribute('user_id'),
                    role: Role::from((string) $model->getAttribute('role')),
                    organizationId: $model->getAttribute('organization_id') === null
                        ? null
                        : (string) $model->getAttribute('organization_id'),
                    assignedAt: $model->getAttribute('assigned_at'),
                ),
            )
            ->all();

        return array_values($assignments);
    }
}
```

Modify `AuthorizationServiceProvider` (full new content):

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleAssignmentRepository;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RoleAssignmentRepository::class,
            EloquentRoleAssignmentRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

**Step 4:** Run again → PASS.

**Step 5: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add Eloquent persistence for RoleAssignment"
```

---

### Task 15: `PermissionChecker` + `RoleAssignmentPermissionChecker`

**Files:**
- Create: `modules/Authorization/Application/Services/PermissionChecker.php`
- Create: `modules/Authorization/Infrastructure/Services/RoleAssignmentPermissionChecker.php`
- Modify: `modules/Authorization/Infrastructure/Providers/AuthorizationServiceProvider.php`
- Test: `modules/Authorization/Tests/Unit/Infrastructure/Services/RoleAssignmentPermissionCheckerTest.php`

**Step 1: Write the failing test** (uses an anonymous class as a test double for the repository — no namespace needed)

```php
<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Services\RoleAssignmentPermissionChecker;

it('confirma un permiso otorgado por alguno de los roles del usuario', function (): void {
    $repository = new class implements RoleAssignmentRepository
    {
        public function save(RoleAssignment $assignment): void {}

        public function findByUserId(string $userId): array
        {
            return [
                RoleAssignment::assign(
                    id: 'assignment-1',
                    userId: $userId,
                    role: Role::Teacher,
                    organizationId: null,
                ),
            ];
        }
    };

    $checker = new RoleAssignmentPermissionChecker($repository);

    expect($checker->userHasPermission('user-1', Permission::ViewOrganizations))->toBeTrue()
        ->and($checker->userHasPermission('user-1', Permission::ManageOrganizations))->toBeFalse();
});

it('niega un permiso cuando el usuario no tiene ninguna asignación', function (): void {
    $repository = new class implements RoleAssignmentRepository
    {
        public function save(RoleAssignment $assignment): void {}

        public function findByUserId(string $userId): array
        {
            return [];
        }
    };

    $checker = new RoleAssignmentPermissionChecker($repository);

    expect($checker->userHasPermission('user-1', Permission::ViewOrganizations))->toBeFalse();
});
```

**Step 2:** Run → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Services;

use Modules\Authorization\Domain\Enums\Permission;

interface PermissionChecker
{
    public function userHasPermission(string $userId, Permission $permission): bool;
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Services;

use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Domain\Services\RolePermissions;

final readonly class RoleAssignmentPermissionChecker implements PermissionChecker
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    public function userHasPermission(string $userId, Permission $permission): bool
    {
        foreach ($this->assignments->findByUserId($userId) as $assignment) {
            if (RolePermissions::grants($assignment->role(), $permission)) {
                return true;
            }
        }

        return false;
    }
}
```

Modify `AuthorizationServiceProvider` — add to `register()`:

```php
        $this->app->bind(
            PermissionChecker::class,
            RoleAssignmentPermissionChecker::class,
        );
```

(with `use Modules\Authorization\Application\Services\PermissionChecker;` and `use Modules\Authorization\Infrastructure\Services\RoleAssignmentPermissionChecker;` added at the top).

**Step 4:** Run again → PASS.

**Step 5: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add PermissionChecker service"
```

---

### Task 16: `EnsurePermission` middleware + `permission` alias

**Files:**
- Create: `modules/Authorization/Presentation/Http/Middleware/EnsurePermission.php`
- Modify: `bootstrap/app.php`

This task has no isolated unit test of its own — it's exercised end-to-end in Task 17, where the first permission-protected route is created. Writing a standalone middleware test without a real route would just re-test the same logic already covered by `RoleAssignmentPermissionCheckerTest`.

**Step 1: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePermission
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiErrorResponse::make(
                message: 'Debe autenticarse para acceder a este recurso.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        $requiredPermission = Permission::from($permission);

        if (! $this->checker->userHasPermission(
            (string) $user->getAuthIdentifier(),
            $requiredPermission,
        )) {
            return ApiErrorResponse::make(
                message: 'No tiene permisos para realizar esta acción.',
                status: 403,
                code: 'FORBIDDEN',
            );
        }

        return $next($request);
    }
}
```

Modify `bootstrap/app.php` — add the `use` statement and the `alias()` call inside `withMiddleware`:

```php
use Modules\Authorization\Presentation\Http\Middleware\EnsurePermission;
```

(add alongside the other `use` statements, alphabetically before `Modules\Foundation\...`)

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationId::class);

        $middleware->alias([
            'permission' => EnsurePermission::class,
        ]);

        $middleware->redirectGuestsTo(
            static function (Request $request): ?string {
                if ($request->is('api/*') || $request->expectsJson()) {
                    return null;
                }

                return '/login';
            },
        );
    })
```

**Step 2: Verify nothing broke**

Run: `docker compose exec app composer quality`
Expected: PASS (no new tests yet, but confirms the file compiles and boots correctly).

**Step 3: Commit**

```bash
git add bootstrap/app.php modules/Authorization
git commit -m "feat(authorization): add EnsurePermission middleware and permission alias"
```

---

### Task 17: `AssignRole` command + protected endpoint + bootstrap console command

**Files:**
- Create: `modules/Authorization/Application/Commands/AssignRoleCommand.php`
- Create: `modules/Authorization/Application/Responses/RoleAssignmentResponse.php`
- Create: `modules/Authorization/Application/UseCases/AssignRoleHandler.php`
- Create: `modules/Authorization/Presentation/Http/Requests/AssignRoleRequest.php`
- Create: `modules/Authorization/Presentation/Http/Controllers/RoleAssignmentController.php`
- Create: `modules/Authorization/Presentation/Console/AssignRoleConsoleCommand.php`
- Modify: `modules/Authorization/Presentation/Routes/api.php`
- Modify: `modules/Authorization/Infrastructure/Providers/AuthorizationServiceProvider.php`
- Test: `modules/Authorization/Tests/Feature/AssignRoleTest.php`

This is the bootstrapping story: the HTTP endpoint requires `roles.manage` (i.e. an existing SuperAdmin) — so the *very first* role assignment on a fresh install has to happen through a console command instead, run directly on the server (`docker compose exec app php artisan authorization:assign-role ...`). This is a known, deliberate gap (documented in the design doc as deferred complexity) — there's no self-service "create the first admin" flow yet.

**Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

function registerUserForAssignRoleTest(): UserModel
{
    /** @var Tests\TestCase $this */
    $repository = test()->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('permite asignar un rol cuando quien llama es superadministrador', function (): void {
    $superAdmin = registerUserForAssignRoleTest();

    /** @var Tests\TestCase $this */
    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: $superAdmin->id,
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($superAdmin);

    $targetUser = registerUserForAssignRoleTest();

    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => $targetUser->id,
        'role' => 'teacher',
    ])
        ->assertCreated()
        ->assertJsonPath('data.userId', $targetUser->id)
        ->assertJsonPath('data.role', 'teacher');

    assertDatabaseHas('authorization_role_assignments', [
        'user_id' => $targetUser->id,
        'role' => 'teacher',
    ]);
});

it('rechaza la asignación de roles a quien no es superadministrador', function (): void {
    $student = registerUserForAssignRoleTest();

    /** @var Tests\TestCase $this */
    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: $student->id,
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($student);

    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => (string) Illuminate\Support\Str::uuid(),
        'role' => 'teacher',
    ])->assertForbidden();
});

it('rechaza la asignación de roles sin autenticación', function (): void {
    postJson('/api/v1/authorization/role-assignments', [
        'user_id' => (string) Illuminate\Support\Str::uuid(),
        'role' => 'teacher',
    ])->assertUnauthorized();
});

it('permite asignar el primer rol mediante el comando de consola', function (): void {
    /** @var Tests\TestCase $this */
    $user = registerUserForAssignRoleTest();

    $this->artisan(
        'authorization:assign-role',
        ['userId' => $user->id, 'role' => 'super_admin'],
    )->assertSuccessful();

    assertDatabaseHas('authorization_role_assignments', [
        'user_id' => $user->id,
        'role' => 'super_admin',
    ]);
});
```

**Step 2:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Feature/AssignRoleTest.php` → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class AssignRoleCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $role,
        public ?string $organizationId,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Responses;

use Modules\Authorization\Domain\Entities\RoleAssignment;

final readonly class RoleAssignmentResponse
{
    private function __construct(
        public string $id,
        public string $userId,
        public string $role,
        public ?string $organizationId,
    ) {}

    public static function fromRoleAssignment(RoleAssignment $assignment): self
    {
        return new self(
            id: $assignment->id(),
            userId: $assignment->userId(),
            role: $assignment->role()->value,
            organizationId: $assignment->organizationId(),
        );
    }

    /**
     * @return array{id: string, userId: string, role: string, organizationId: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'role' => $this->role,
            'organizationId' => $this->organizationId,
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;

final readonly class AssignRoleHandler
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    public function handle(
        AssignRoleCommand $command,
    ): RoleAssignmentResponse {
        $assignment = RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $command->userId,
            role: Role::from($command->role),
            organizationId: $command->organizationId,
        );

        $this->assignments->save($assignment);

        return RoleAssignmentResponse::fromRoleAssignment($assignment);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Authorization\Domain\Enums\Role;

final class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
            ],
            'role' => [
                'required',
                'string',
                new Enum(Role::class),
            ],
            'organization_id' => [
                'nullable',
                'uuid',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'El identificador del usuario es obligatorio.',
            'user_id.uuid' => 'El identificador del usuario debe ser un UUID válido.',
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol no es válido.',
            'organization_id.uuid' => 'El identificador de la organización debe ser un UUID válido.',
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Presentation\Http\Requests\AssignRoleRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final class RoleAssignmentController
{
    public function store(
        AssignRoleRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new AssignRoleCommand(
                userId: (string) $validated['user_id'],
                role: (string) $validated['role'],
                organizationId: isset($validated['organization_id'])
                    ? (string) $validated['organization_id']
                    : null,
            ),
        );

        assert($result instanceof RoleAssignmentResponse);

        return response()->json(
            ['data' => $result->toArray()],
            Response::HTTP_CREATED,
        );
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Console;

use Illuminate\Console\Command as ConsoleCommand;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Foundation\Application\Bus\CommandBus;

final class AssignRoleConsoleCommand extends ConsoleCommand
{
    /**
     * @var string
     */
    protected $signature = 'authorization:assign-role {userId} {role} {--organization=}';

    /**
     * @var string
     */
    protected $description = 'Asigna un rol de autorización a un usuario (uso principal: bootstrap del primer superadministrador).';

    public function handle(CommandBus $commandBus): int
    {
        $commandBus->dispatch(
            new AssignRoleCommand(
                userId: (string) $this->argument('userId'),
                role: (string) $this->argument('role'),
                organizationId: $this->option('organization'),
            ),
        );

        $this->info('Rol asignado correctamente.');

        return self::SUCCESS;
    }
}
```

Modify `modules/Authorization/Presentation/Routes/api.php` (full new content):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Presentation\Http\Controllers\AuthorizationStatusController;
use Modules\Authorization\Presentation\Http\Controllers\RoleAssignmentController;

Route::prefix('api/v1/authorization')
    ->name('api.v1.authorization.')
    ->group(function (): void {
        Route::get('/status', AuthorizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/role-assignments', [RoleAssignmentController::class, 'store'])
                ->middleware('permission:roles.manage')
                ->name('role-assignments.store');
        });
    });
```

Modify `AuthorizationServiceProvider` (full new content):

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Application\UseCases\AssignRoleHandler;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Services\RoleAssignmentPermissionChecker;
use Modules\Authorization\Presentation\Console\AssignRoleConsoleCommand;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RoleAssignmentRepository::class,
            EloquentRoleAssignmentRepository::class,
        );

        $this->app->bind(
            PermissionChecker::class,
            RoleAssignmentPermissionChecker::class,
        );
    }

    public function boot(
        MessageHandlerRegistry $registry,
    ): void {
        $registry->register(
            AssignRoleCommand::class,
            AssignRoleHandler::class,
        );

        $this->commands([
            AssignRoleConsoleCommand::class,
        ]);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}
```

**Step 4:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Feature/AssignRoleTest.php` → PASS.

**Step 5: Commit**

```bash
git add modules/Authorization
git commit -m "feat(authorization): add AssignRole command, protected endpoint and bootstrap console command"
```

---

### Task 18: `ListMyRoles` query + endpoint

**Files:**
- Create: `modules/Authorization/Application/Queries/ListMyRolesQuery.php`
- Create: `modules/Authorization/Application/UseCases/ListMyRolesHandler.php`
- Create: `modules/Authorization/Presentation/Http/Controllers/MyRolesController.php`
- Modify: `modules/Authorization/Presentation/Routes/api.php`
- Modify: `modules/Authorization/Infrastructure/Providers/AuthorizationServiceProvider.php`
- Test: `modules/Authorization/Tests/Feature/MyRolesTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\getJson;

it('lista los roles del usuario autenticado', function (): void {
    /** @var Tests\TestCase $this */
    $userRepository = $this->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Docente EDUDRIVE',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $userRepository->save($user);

    $this->app->make(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    getJson('/api/v1/authorization/me/roles')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', 'teacher');
});

it('rechaza la consulta de roles sin autenticación', function (): void {
    getJson('/api/v1/authorization/me/roles')->assertUnauthorized();
});
```

**Step 2:** Run `docker compose exec app php artisan test modules/Authorization/Tests/Feature/MyRolesTest.php` → FAIL.

**Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListMyRolesQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\UseCases;

use Modules\Authorization\Application\Queries\ListMyRolesQuery;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;

final readonly class ListMyRolesHandler
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    /**
     * @return list<RoleAssignmentResponse>
     */
    public function handle(ListMyRolesQuery $query): array
    {
        return array_map(
            static fn (RoleAssignment $assignment): RoleAssignmentResponse => RoleAssignmentResponse::fromRoleAssignment($assignment),
            $this->assignments->findByUserId($query->userId),
        );
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Queries\ListMyRolesQuery;
use Modules\Authorization\Application\Responses\RoleAssignmentResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class MyRolesController
{
    public function __invoke(
        Request $request,
        QueryBus $queryBus,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $result = $queryBus->ask(
            new ListMyRolesQuery(
                userId: (string) $user->getAuthIdentifier(),
            ),
        );

        assert(is_array($result));

        /** @var list<RoleAssignmentResponse> $result */
        $data = array_map(
            static fn (RoleAssignmentResponse $assignment): array => $assignment->toArray(),
            $result,
        );

        return response()->json(['data' => $data]);
    }
}
```

Modify `modules/Authorization/Presentation/Routes/api.php` — add inside the `auth:sanctum` group, before `role-assignments`:

```php
            Route::get('/me/roles', MyRolesController::class)
                ->name('me.roles');
```

(add `use Modules\Authorization\Presentation\Http\Controllers\MyRolesController;` at the top).

Modify `AuthorizationServiceProvider` — add to `boot()`:

```php
        $registry->register(
            ListMyRolesQuery::class,
            ListMyRolesHandler::class,
        );
```

**Step 4:** Run again → PASS.

**Step 5: Run the whole Authorization suite, then commit**

Run: `docker compose exec app php artisan test modules/Authorization`
Expected: all PASS

```bash
git add modules/Authorization
git commit -m "feat(authorization): add ListMyRoles query and endpoint"
```

---

### Task 19: Protect Organization write endpoints with `permission:organizations.manage`

**Files:**
- Modify: `modules/Organization/Presentation/Routes/api.php`
- Modify: `modules/Organization/Tests/Feature/CreateOrganizationTest.php`
- Modify: `modules/Organization/Tests/Feature/AddCampusTest.php`

This is the integration point between the two modules: from now on, creating an organization or adding a campus requires the `organizations.manage` permission (SuperAdmin only, per `RolePermissions`), not just authentication. Listing organizations (`GET /`) stays open to any authenticated user.

**Step 1: Update the existing feature tests to reflect the new requirement (write the failing assertions first)**

In `CreateOrganizationTest.php`, replace the `actingAsAuthenticatedUser()` helper's body so a plain authenticated user is a **student** by default, and add a new test that a student gets 403:

```php
function actingAsAuthenticatedUser(): UserModel
{
    /** @var Tests\TestCase $this */
    $repository = test()->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    test()->app->make(\Modules\Authorization\Domain\Repositories\RoleAssignmentRepository::class)->save(
        \Modules\Authorization\Domain\Entities\RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: $user->id(),
            role: \Modules\Authorization\Domain\Enums\Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($model);

    return $model;
}
```

Add a new test in the same file:

```php
it('rechaza la creación de organizaciones a un usuario sin el permiso organizations.manage', function (): void {
    /** @var Tests\TestCase $this */
    $repository = test()->app->make(UserRepository::class);

    $user = User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Illuminate\Support\Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    test()->app->make(\Modules\Authorization\Domain\Repositories\RoleAssignmentRepository::class)->save(
        \Modules\Authorization\Domain\Entities\RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: $user->id(),
            role: \Modules\Authorization\Domain\Enums\Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson('/api/v1/organizations', [
        'name' => 'Organización sin permiso',
        'type' => 'company',
    ])->assertForbidden();
});
```

Apply the equivalent change to `AddCampusTest.php`'s `anAuthenticatedUserForAddCampusTest()` helper (make it a SuperAdmin the same way) and add an analogous "rejects a student" test for the `addCampus` endpoint.

**Step 2:** Run `docker compose exec app php artisan test modules/Organization` → FAIL (all authenticated-but-not-superadmin requests now correctly need to be 403, but the routes aren't gated yet, so they'll currently succeed with 201 instead — that's the expected failure to fix in Step 3).

**Step 3: Implement** — modify `modules/Organization/Presentation/Routes/api.php` (full new content):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationController;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/', [OrganizationController::class, 'index'])
                ->name('index');

            Route::middleware('permission:organizations.manage')->group(function (): void {
                Route::post('/', [OrganizationController::class, 'store'])
                    ->name('store');

                Route::post('/{organizationId}/campuses', [OrganizationController::class, 'addCampus'])
                    ->whereUuid('organizationId')
                    ->name('campuses.store');
            });
        });
    });
```

**Step 4:** Run `docker compose exec app php artisan test modules/Organization` → PASS.

**Step 5: Commit**

```bash
git add modules/Organization
git commit -m "feat(organization): require organizations.manage permission on write endpoints"
```

---

### Task 20: Update engineering log, final full validation, close out the slice

**Files:**
- Modify: `docs/engineering/ENG-LOG.md`
- Modify: `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`

**Step 1: Run the entire suite and quality gate**

```bash
docker compose exec app composer quality
```

Expected: PASS (Pint, PHPStan, and every test across `Foundation`, `Identity`, `Audit`, `Academic`, `Organization` and `Authorization`).

**Step 2: Append a new dated entry to `docs/engineering/ENG-LOG.md`**, following the exact style of the existing `IMP-020`/`IMP-021` entries (one `##` heading per bloque, `### Completado` bullet list, `### Validaciones` with the three composer commands and their result, closing `**Estado:** Finalizado.`). Summarize: Organization module (aggregate, campuses, CRUD-ish endpoints), Authorization module (roles/permissions catalog, RoleAssignment, PermissionChecker, `permission` middleware, bootstrap console command), and the wiring of `organizations.manage` onto Organization's write endpoints.

**Step 3: Update `docs/roadmap/ENG-000-roadmap-tecnico-backend.md`**:
- Mark the reduced-scope Authorization/Organizations slice as **Completado** in the "Historia técnica activa" section (section 25), and note explicitly what was deferred (additional roles, org-scoped permissions, membership history, groups/cohorts, consent) so nobody assumes ENG-012–ENG-019 are fully done.
- Set the new "Historia técnica activa" to resuming Academic (Fase 5) where it was paused, per the note already added in section 11 during the previous reconciliation.
- Bump the version/date in the header table and add a row to the "Control de cambios" table.

**Step 4: Commit**

```bash
git add docs/engineering/ENG-LOG.md docs/roadmap/ENG-000-roadmap-tecnico-backend.md
git commit -m "docs(roadmap): close out reduced-scope Authorization/Organizations slice"
```

---

## Explicitly out of scope (don't build these — they're deferred by design)

- Roles beyond the 4 listed (`Coordinador`, `Evaluador`, `Soporte`, `Integración SIMUDRIVE`).
- Organization-scoped permission checks (e.g. an Institutional Admin only managing *their own* organization) — `PermissionChecker` today is a global yes/no per user, not scoped by `organization_id`.
- Membership change history, revocation of a `RoleAssignment` (it's append-only for now — no `DELETE` endpoint).
- Groups/cohorts (`ENG-019`), consent/privacy (`ENG-023`), advanced access auditing.
- A self-service "create the first admin" HTTP flow — bootstrapping is CLI-only (`authorization:assign-role`), which is fine for local/dev but is a real gap before any production deployment (worth its own ENG story later).
- Retrofitting the `permission` middleware onto Academic's existing course endpoints — not part of the agreed design; would be unrelated scope creep and risk breaking the already-green Academic test suite.
