# Completar ENG-026 (Cursos) — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Completar ENG-026 (Cursos) del módulo `Academic`: agregar los campos de dominio que faltan (objetivos, requisitos, modalidad, duración), exponer `publish`/`archive` como endpoints reales, proteger todos los endpoints de `Academic` con el mismo modelo de permisos que `Organization`, y corregir el bug de manejo de excepciones detectado (excepciones de Academic no capturadas por el manejador global, devolviendo 500 en vez del código HTTP correcto).

**Architecture:** Se extiende el agregado `Course` ya existente (no se crea uno nuevo) con 4 campos opcionales y un enum `CourseModality`. Se reusa el mismo patrón `CommandBus`/`QueryBus` ya establecido para `publish`/`archive`, llamando a los métodos de dominio `Course::publish()`/`Course::archive()` que ya existen y están probados a nivel unitario, pero que hasta hoy no tenían ningún caso de uso ni endpoint que los invocara. Las excepciones de dominio/aplicación de Academic pasan a extender `Modules\Foundation\Domain\Exceptions\DomainException` (en vez de `\DomainException` de PHP), activando el manejador de excepciones genérico ya registrado en `bootstrap/app.php` — sin tocar ese archivo. Los permisos `courses.manage`/`courses.view` siguen exactamente el mismo patrón que `organizations.manage`/`organizations.view` en el módulo `Authorization`.

**Tech Stack:** Laravel 12, PHP 8.4, Pest, Larastan/PHPStan, Pint.

## Global Constraints

- PHP 8.4 / Laravel 12, sin cambios de versión ni de dependencias.
- `declare(strict_types=1);` en todo archivo PHP nuevo.
- Todos los comandos de PHP/Composer se ejecutan vía Docker (ver "Convenciones" abajo).
- `composer quality` (Pint check + Larastan/PHPStan + Pest completo) en verde después de cada tarea, antes de comitear.
- No se modifica `bootstrap/app.php` — el manejador genérico de `DomainException` ya registrado ahí captura automáticamente cualquier excepción que extienda esa clase base, sin importar el módulo.
- No se modifica ningún archivo de `Organization`/`Identity` — solo se reusan `actingAsSuperAdminUser()`/`actingAsAuthenticatedUser()` (helpers ya existentes en `tests/Pest.php`) y las entidades `RoleAssignment`/`User` ya existentes de esos módulos, igual que ya hacen los tests de `Organization`.
- **Cambio de comportamiento deliberado**: hoy `GET`/`POST /api/v1/academic/courses` no exigen autenticación en absoluto. Esta historia les agrega `auth:sanctum` + permiso, igual que `Organization`. No hay consumidores en producción todavía (proyecto pre-lanzamiento).
- Diseño aprobado: `docs/plans/2026-08-01-cursos-completar-eng026-design.md`.

---

## Convenciones usadas en este plan (leer una vez)

- Todos los comandos PHP/Composer se ejecutan vía Docker:
  ```bash
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos <comando>
  ```
  Si la imagen no existe todavía en el worktree, construirla una vez antes de la Tarea 1:
  ```bash
  MSYS_NO_PATHCONV=1 docker build -t edudrive-worktree-academic-cursos -f docker/php/Dockerfile .
  ```
  Después, dentro del worktree: `composer install`, `cp .env.example .env && php artisan key:generate` (usando el mismo `docker run` con `-e HOME=/tmp` si se usa `php artisan tinker`/comandos interactivos, para evitar el error de permisos de `psysh`).
- Este plan **no toca** `resources/css/app.css`, `resources/js/app.js` ni ninguna vista Blade — es trabajo puramente de backend (dominio, aplicación, presentación HTTP). No hace falta `npm install`/`npm run build` en ninguna tarea.
- Las pruebas usan SQLite en memoria (`phpunit.xml`, `RefreshDatabase`) — no requieren PostgreSQL/Redis reales.
- Los helpers `actingAsAuthenticatedUser()`/`actingAsSuperAdminUser()` (en `tests/Pest.php`) ya existen y hacen `Sanctum::actingAs()` internamente — correcto y suficiente para estos tests, que son endpoints JSON protegidos por `auth:sanctum` (no rutas web con guard de sesión, a diferencia del panel de organizaciones). No hace falta `$this->actingAs(..., 'web')` en ningún test de este plan.
- Después de **cada** tarea: correr `composer quality` y confirmar verde antes de comitear.

---

### Task 1: Campos nuevos del curso (dominio, migración, persistencia)

**Files:**
- Create: `modules/Academic/Domain/Enums/CourseModality.php`
- Modify: `modules/Academic/Domain/Aggregates/Course.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseModel.php`
- Modify: `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php`
- Create: `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_01_000001_add_details_to_academic_courses_table.php`
- Test: `modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php` (modificar)
- Test: `modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php` (modificar)

**Interfaces:**
- Consumes: nada nuevo de otras tareas.
- Produces: `Course::create()`/`Course::restore()` con 4 parámetros opcionales nuevos (`?string $objectives`, `?string $prerequisites`, `?CourseModality $modality`, `?int $durationHours`), y sus getters `objectives(): ?string`, `prerequisites(): ?string`, `modality(): ?CourseModality`, `durationHours(): ?int`. `CourseModality` (enum respaldado por string: `InPerson`='in_person', `Virtual`='virtual', `Hybrid`='hybrid'). Las Tareas 2-5 no dependen de esta interfaz directamente (los endpoints de publicar/archivar no tocan estos campos), pero la Tarea 3 sí la consume al extender `CreateCourseCommand`/`Handler`/`Request`/`Response`.

- [ ] **Step 1: Escribir las pruebas unitarias que deben fallar**

Modificar `modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php`: agregar el import `use Modules\Academic\Domain\Enums\CourseModality;` junto a los demás `use`, y agregar estos tres tests al final del archivo (después del último `it(...)` existente):

```php
it('crea un curso con objetivos, requisitos, modalidad y duración', function (): void {
    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        description: 'Curso introductorio de EDUDRIVE.',
        objectives: 'Comprender los principios básicos de seguridad vial.',
        prerequisites: 'Ninguno.',
        modality: CourseModality::Virtual,
        durationHours: 20,
    );

    expect($course->objectives())
        ->toBe('Comprender los principios básicos de seguridad vial.')
        ->and($course->prerequisites())
        ->toBe('Ninguno.')
        ->and($course->modality())
        ->toBe(CourseModality::Virtual)
        ->and($course->durationHours())
        ->toBe(20);
});

it('normaliza objetivos y requisitos vacíos como nulos', function (): void {
    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c0'),
        code: CourseCode::fromString('EDU-001'),
        title: CourseTitle::fromString('Introducción a la seguridad vial'),
        objectives: '   ',
        prerequisites: '   ',
    );

    expect($course->objectives())->toBeNull()
        ->and($course->prerequisites())->toBeNull();
});

it('permite crear un curso sin los campos opcionales nuevos', function (): void {
    $course = createAcademicCourse();

    expect($course->objectives())->toBeNull()
        ->and($course->prerequisites())->toBeNull()
        ->and($course->modality())->toBeNull()
        ->and($course->durationHours())->toBeNull();
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php
```
Expected: FAIL (los parámetros `objectives`/`prerequisites`/`modality`/`durationHours` no existen todavía en `Course::create()`, y la clase `CourseModality` no existe).

- [ ] **Step 3: Crear el enum `CourseModality`**

Crear `modules/Academic/Domain/Enums/CourseModality.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseModality: string
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';
}
```

- [ ] **Step 4: Extender el agregado `Course`**

Reemplazar el contenido completo de `modules/Academic/Domain/Aggregates/Course.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\ArchivedCourseCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseAlreadyArchived;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

final class Course
{
    private function __construct(
        private readonly CourseId $id,
        private readonly CourseCode $code,
        private CourseTitle $title,
        private ?string $description,
        private ?string $objectives,
        private ?string $prerequisites,
        private ?CourseModality $modality,
        private ?int $durationHours,
        private CourseStatus $status,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
    ) {}

    public static function create(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description = null,
        ?string $objectives = null,
        ?string $prerequisites = null,
        ?CourseModality $modality = null,
        ?int $durationHours = null,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: CourseStatus::Draft,
            publishedAt: null,
            archivedAt: null,
        );
    }

    public static function restore(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description,
        ?string $objectives,
        ?string $prerequisites,
        ?CourseModality $modality,
        ?int $durationHours,
        CourseStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: $status,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
        );
    }

    public function rename(CourseTitle $title): void
    {
        $this->ensureIsNotArchived();

        $this->title = $title;
    }

    public function changeDescription(?string $description): void
    {
        $this->ensureIsNotArchived();

        $this->description = self::normalizeText($description);
    }

    public function publish(DateTimeImmutable $publishedAt): void
    {
        $this->ensureIsNotArchived();

        if ($this->status->isPublished()) {
            throw CourseAlreadyPublished::create();
        }

        $this->status = CourseStatus::Published;
        $this->publishedAt = $publishedAt;
    }

    public function archive(DateTimeImmutable $archivedAt): void
    {
        if ($this->status->isArchived()) {
            throw CourseAlreadyArchived::create();
        }

        $this->status = CourseStatus::Archived;
        $this->archivedAt = $archivedAt;
    }

    public function id(): CourseId
    {
        return $this->id;
    }

    public function code(): CourseCode
    {
        return $this->code;
    }

    public function title(): CourseTitle
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function objectives(): ?string
    {
        return $this->objectives;
    }

    public function prerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function modality(): ?CourseModality
    {
        return $this->modality;
    }

    public function durationHours(): ?int
    {
        return $this->durationHours;
    }

    public function status(): CourseStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    private function ensureIsNotArchived(): void
    {
        if ($this->status->isArchived()) {
            throw ArchivedCourseCannotBeModified::create();
        }
    }

    private static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
```

Nota: el método privado `normalizeDescription` se renombró a `normalizeText` porque ahora se reusa también para `objectives`/`prerequisites` — es un método privado, no cambia ninguna interfaz pública.

- [ ] **Step 5: Migración para las columnas nuevas**

Crear `modules/Academic/Infrastructure/Persistence/Migrations/2026_08_01_000001_add_details_to_academic_courses_table.php`:
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
        Schema::table('academic_courses', function (Blueprint $table): void {
            $table->text('objectives')->nullable()->after('description');
            $table->text('prerequisites')->nullable()->after('objectives');
            $table->string('modality', 30)->nullable()->after('prerequisites');
            $table->unsignedInteger('duration_hours')->nullable()->after('modality');
        });
    }

    public function down(): void
    {
        Schema::table('academic_courses', function (Blueprint $table): void {
            $table->dropColumn(['objectives', 'prerequisites', 'modality', 'duration_hours']);
        });
    }
};
```

- [ ] **Step 6: Actualizar `CourseModel` (cast del campo numérico)**

Modificar `modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseModel.php`, reemplazar el método `casts()`:
```php
    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'duration_hours' => 'integer',
        ];
    }
```

- [ ] **Step 7: Actualizar `EloquentCourseRepository`**

Reemplazar el contenido completo de `modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;

final class EloquentCourseRepository implements CourseRepository
{
    public function save(Course $course): void
    {
        CourseModel::query()->updateOrCreate(
            [
                'id' => $course->id()->value(),
            ],
            [
                'code' => $course->code()->value(),
                'title' => $course->title()->value(),
                'description' => $course->description(),
                'objectives' => $course->objectives(),
                'prerequisites' => $course->prerequisites(),
                'modality' => $course->modality()?->value,
                'duration_hours' => $course->durationHours(),
                'status' => $course->status()->value,
                'published_at' => $course->publishedAt(),
                'archived_at' => $course->archivedAt(),
            ],
        );
    }

    public function findById(CourseId $id): ?Course
    {
        $model = CourseModel::query()->find($id->value());

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function findByCode(CourseCode $code): ?Course
    {
        $model = CourseModel::query()
            ->where('code', $code->value())
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function existsByCode(CourseCode $code): bool
    {
        return CourseModel::query()
            ->where('code', $code->value())
            ->exists();
    }

    /**
     * @return list<Course>
     */
    public function all(): array
    {
        $courses = CourseModel::query()
            ->orderBy('created_at')
            ->get()
            ->map(
                fn (CourseModel $model): Course => $this->toDomain($model),
            )
            ->all();

        return array_values($courses);
    }

    private function toDomain(CourseModel $model): Course
    {
        $modality = $model->getAttribute('modality');

        return Course::restore(
            id: CourseId::fromString((string) $model->getAttribute('id')),
            code: CourseCode::fromString((string) $model->getAttribute('code')),
            title: CourseTitle::fromString((string) $model->getAttribute('title')),
            description: $this->nullableString(
                $model->getAttribute('description'),
            ),
            objectives: $this->nullableString(
                $model->getAttribute('objectives'),
            ),
            prerequisites: $this->nullableString(
                $model->getAttribute('prerequisites'),
            ),
            modality: $modality === null ? null : CourseModality::from((string) $modality),
            durationHours: $model->getAttribute('duration_hours'),
            status: CourseStatus::from(
                (string) $model->getAttribute('status'),
            ),
            publishedAt: $this->toDateTimeImmutable(
                $model->getAttribute('published_at'),
            ),
            archivedAt: $this->toDateTimeImmutable(
                $model->getAttribute('archived_at'),
            ),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function toDateTimeImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
```

- [ ] **Step 8: Correr las pruebas unitarias y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php
```
Expected: PASS.

- [ ] **Step 9: Agregar y correr la prueba de integración del repositorio**

Modificar `modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php`: agregar el import `use Modules\Academic\Domain\Enums\CourseModality;` y este test al final del archivo:
```php
it('guarda y recupera los campos nuevos de un curso (objetivos, requisitos, modalidad, duración)', function (): void {
    $repository = app(EloquentCourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f915c3'),
        code: CourseCode::fromString('EDU-004'),
        title: CourseTitle::fromString('Manejo defensivo'),
        objectives: 'Aplicar técnicas de manejo defensivo.',
        prerequisites: 'Licencia de conducir vigente.',
        modality: CourseModality::Hybrid,
        durationHours: 15,
    );

    $repository->save($course);

    $storedCourse = $repository->findById($course->id());

    expect($storedCourse?->objectives())
        ->toBe('Aplicar técnicas de manejo defensivo.')
        ->and($storedCourse?->prerequisites())
        ->toBe('Licencia de conducir vigente.')
        ->and($storedCourse?->modality())
        ->toBe(CourseModality::Hybrid)
        ->and($storedCourse?->durationHours())
        ->toBe(15);
});
```

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
```
Expected: PASS.

- [ ] **Step 10: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos composer quality
```
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add modules/Academic/Domain/Enums/CourseModality.php modules/Academic/Domain/Aggregates/Course.php modules/Academic/Infrastructure/Persistence/Eloquent/Models/CourseModel.php modules/Academic/Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php modules/Academic/Infrastructure/Persistence/Migrations/2026_08_01_000001_add_details_to_academic_courses_table.php modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php modules/Academic/Tests/Integration/EloquentCourseRepositoryTest.php
git commit -m "feat(academic): add objectives, prerequisites, modality and duration to Course"
```

---

### Task 2: Permisos de cursos (`courses.manage` / `courses.view`)

**Files:**
- Modify: `modules/Authorization/Domain/Enums/Permission.php`
- Modify: `modules/Authorization/Domain/Services/RolePermissions.php`
- Test: `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php` (modificar)

**Interfaces:**
- Consumes: nada de la Tarea 1.
- Produces: `Permission::ManageCourses` (`'courses.manage'`), `Permission::ViewCourses` (`'courses.view'`); `RolePermissions::grants(Role::SuperAdmin, Permission::ManageCourses|ViewCourses)` → `true`; `RolePermissions::grants(Role::InstitutionalAdmin|Teacher|Student, Permission::ViewCourses)` → `true`, `ManageCourses` → `false`. La Tarea 3 consume estos dos casos del enum en las rutas de `Academic`.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Modificar `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`, agregar al final del archivo:
```php
it('otorga los permisos de cursos al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewCourses))->toBeTrue();
});

it('solo otorga permiso de visualización de cursos a administradores institucionales, docentes y estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageCourses))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageCourses))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageCourses))->toBeFalse();
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
```
Expected: FAIL (`Permission::ManageCourses`/`ViewCourses` no existen todavía).

- [ ] **Step 3: Agregar los casos al enum `Permission`**

Reemplazar el contenido completo de `modules/Authorization/Domain/Enums/Permission.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Permission: string
{
    case ManageOrganizations = 'organizations.manage';
    case ViewOrganizations = 'organizations.view';
    case ManageRoleAssignments = 'roles.manage';
    case ManageCourses = 'courses.manage';
    case ViewCourses = 'courses.view';
}
```

- [ ] **Step 4: Actualizar `RolePermissions`**

Reemplazar el contenido completo de `modules/Authorization/Domain/Services/RolePermissions.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Services;

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;

final class RolePermissions
{
    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }

    /**
     * @return list<Permission>
     */
    private static function permissionsFor(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => [
                Permission::ManageOrganizations,
                Permission::ViewOrganizations,
                Permission::ManageRoleAssignments,
                Permission::ManageCourses,
                Permission::ViewCourses,
            ],
            Role::InstitutionalAdmin, Role::Teacher, Role::Student => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
            ],
        };
    }
}
```

- [ ] **Step 5: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
```
Expected: PASS.

- [ ] **Step 6: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos composer quality
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add modules/Authorization/Domain/Enums/Permission.php modules/Authorization/Domain/Services/RolePermissions.php modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php
git commit -m "feat(authorization): add courses.manage and courses.view permissions"
```

---

### Task 3: Proteger crear/listar cursos, exponer campos nuevos y corregir excepciones

**Files:**
- Modify: `modules/Academic/Application/Commands/CreateCourseCommand.php`
- Modify: `modules/Academic/Application/UseCases/CreateCourseHandler.php`
- Modify: `modules/Academic/Application/Responses/CreateCourseResponse.php`
- Modify: `modules/Academic/Application/Responses/CourseListItemResponse.php`
- Modify: `modules/Academic/Presentation/Http/Requests/CreateCourseRequest.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Academic/Domain/Exceptions/CourseAlreadyPublished.php`
- Modify: `modules/Academic/Domain/Exceptions/CourseAlreadyArchived.php`
- Modify: `modules/Academic/Domain/Exceptions/ArchivedCourseCannotBeModified.php`
- Modify: `modules/Academic/Application/Exceptions/CourseCodeAlreadyExists.php`
- Test: `modules/Academic/Tests/Feature/CreateCourseTest.php` (reescribir)
- Test: `modules/Academic/Tests/Feature/ListCoursesTest.php` (reescribir)

**Interfaces:**
- Consumes: `Course::create()` con los 8 parámetros de la Tarea 1; `Permission::ManageCourses`/`ViewCourses` de la Tarea 2; helpers de test `actingAsSuperAdminUser()`/`actingAsAuthenticatedUser()` (ya existentes en `tests/Pest.php`); entidades `User`/`RoleAssignment` (ya existentes, usadas igual que en los tests de `Organization`).
- Produces: `CreateCourseCommand` con 7 propiedades (`code`, `title`, `description`, `objectives`, `prerequisites`, `modality` como `?string`, `durationHours`); rutas `GET/POST /api/v1/academic/courses` protegidas por `auth:sanctum` + `permission:courses.view`/`permission:courses.manage`. Las Tareas 4 y 5 dependen de que este archivo de rutas y el grupo de middleware `auth:sanctum` ya existan para agregar sus propias rutas dentro del mismo grupo.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Reemplazar el contenido completo de `modules/Academic/Tests/Feature/CreateCourseTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
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

use Tests\TestCase;

it('crea un curso académico', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $response = postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'edu-010',
            'title' => 'Introducción a la seguridad vial',
            'description' => 'Curso base de EDUDRIVE.',
            'objectives' => 'Comprender los principios básicos.',
            'prerequisites' => 'Ninguno.',
            'modality' => 'virtual',
            'duration_hours' => 20,
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.code', 'EDU-010')
        ->assertJsonPath(
            'data.title',
            'Introducción a la seguridad vial',
        )
        ->assertJsonPath(
            'data.description',
            'Curso base de EDUDRIVE.',
        )
        ->assertJsonPath('data.objectives', 'Comprender los principios básicos.')
        ->assertJsonPath('data.prerequisites', 'Ninguno.')
        ->assertJsonPath('data.modality', 'virtual')
        ->assertJsonPath('data.duration_hours', 20)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'title',
                'description',
                'objectives',
                'prerequisites',
                'modality',
                'duration_hours',
                'status',
            ],
        ]);

    assertDatabaseHas('academic_courses', [
        'code' => 'EDU-010',
        'title' => 'Introducción a la seguridad vial',
        'description' => 'Curso base de EDUDRIVE.',
        'objectives' => 'Comprender los principios básicos.',
        'prerequisites' => 'Ninguno.',
        'modality' => 'virtual',
        'duration_hours' => 20,
        'status' => 'draft',
    ]);
});

it('crea un curso académico sin los campos opcionales nuevos', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $response = postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'edu-011',
            'title' => 'Conducción responsable',
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.objectives', null)
        ->assertJsonPath('data.prerequisites', null)
        ->assertJsonPath('data.modality', null)
        ->assertJsonPath('data.duration_hours', null);
});

it('rechaza la creación de un curso sin datos obligatorios', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
            'title',
        ]);
});

it('rechaza un código con formato inválido', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'EDU_010',
            'title' => 'Curso inválido',
        ],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
        ]);
});

it('rechaza una modalidad inválida', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson(
        '/api/v1/academic/courses',
        [
            'code' => 'EDU-012',
            'title' => 'Curso con modalidad inválida',
            'modality' => 'no-existe',
        ],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'modality',
        ]);
});

it('rechaza un código de curso duplicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-013',
        'title' => 'Curso original',
    ])->assertCreated();

    postJson('/api/v1/academic/courses', [
        'code' => 'edu-013',
        'title' => 'Curso duplicado',
    ])
        ->assertConflict()
        ->assertJsonPath('code', 'COURSE_CODE_ALREADY_EXISTS');
});

it('rechaza la creación sin autenticación', function (): void {
    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-014',
        'title' => 'Curso sin sesión',
    ])->assertUnauthorized();
});

it('rechaza la creación de cursos a un usuario sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson('/api/v1/academic/courses', [
        'code' => 'EDU-015',
        'title' => 'Curso sin permiso',
    ])->assertForbidden();
});
```

Reemplazar el contenido completo de `modules/Academic/Tests/Feature/ListCoursesTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use Tests\TestCase;

uses(RefreshDatabase::class);

it('lists academic courses', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    CourseModel::query()->create([
        'id' => '3fdab59d-7431-440f-bdab-f55798e99a79',
        'code' => 'SV-001',
        'title' => 'Seguridad Vial',
        'description' => 'Curso introductorio de seguridad vial.',
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    CourseModel::query()->create([
        'id' => '0844b7fa-5d71-41c6-a59d-864cf7927cc3',
        'code' => 'MOT-001',
        'title' => 'Conducción de Motocicletas',
        'description' => null,
        'status' => 'draft',
        'published_at' => null,
        'archived_at' => null,
    ]);

    $response = $this->getJson('/api/v1/academic/courses');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'SV-001')
        ->assertJsonPath('data.0.title', 'Seguridad Vial')
        ->assertJsonPath('data.0.status', 'draft')
        ->assertJsonPath('data.1.code', 'MOT-001')
        ->assertJsonPath('data.1.title', 'Conducción de Motocicletas')
        ->assertJsonPath('data.1.status', 'draft');
});

it('rechaza el listado sin autenticación', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/academic/courses')->assertUnauthorized();
});

it('permite listar a un usuario con solo el permiso courses.view', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    $this->getJson('/api/v1/academic/courses')->assertOk();
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/CreateCourseTest.php modules/Academic/Tests/Feature/ListCoursesTest.php
```
Expected: FAIL (los campos nuevos no se envían/leen todavía en el comando/respuesta, las rutas no piden autenticación todavía, el código duplicado devuelve 500 en vez de 409).

- [ ] **Step 3: Corregir la clase base de las excepciones**

Reemplazar el contenido completo de `modules/Academic/Domain/Exceptions/CourseAlreadyPublished.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseAlreadyPublished extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curso ya está publicado.',
            errorCode: 'COURSE_ALREADY_PUBLISHED',
            statusCode: 422,
        );
    }
}
```

Reemplazar el contenido completo de `modules/Academic/Domain/Exceptions/CourseAlreadyArchived.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseAlreadyArchived extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El curso ya está archivado.',
            errorCode: 'COURSE_ALREADY_ARCHIVED',
            statusCode: 422,
        );
    }
}
```

Reemplazar el contenido completo de `modules/Academic/Domain/Exceptions/ArchivedCourseCannotBeModified.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ArchivedCourseCannotBeModified extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Un curso archivado no puede ser modificado.',
            errorCode: 'ARCHIVED_COURSE_CANNOT_BE_MODIFIED',
            statusCode: 422,
        );
    }
}
```

Reemplazar el contenido completo de `modules/Academic/Application/Exceptions/CourseCodeAlreadyExists.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseCodeAlreadyExists extends DomainException
{
    public static function forCode(CourseCode $code): self
    {
        return new self(
            message: sprintf(
                'Ya existe un curso con el código %s.',
                $code->value(),
            ),
            errorCode: 'COURSE_CODE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}
```

Nota: `modules/Academic/Tests/Unit/Domain/Aggregates/CourseTest.php` ya usa `->throws(CourseAlreadyPublished::class, 'El curso ya está publicado.')` (y equivalente para `CourseAlreadyArchived`/`ArchivedCourseCannotBeModified`) — como el mensaje no cambia, esas pruebas siguen pasando sin tocarlas.

- [ ] **Step 4: Extender `CreateCourseCommand`**

Reemplazar el contenido completo de `modules/Academic/Application/Commands/CreateCourseCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateCourseCommand implements Command
{
    public function __construct(
        public string $code,
        public string $title,
        public ?string $description,
        public ?string $objectives,
        public ?string $prerequisites,
        public ?string $modality,
        public ?int $durationHours,
    ) {}
}
```

- [ ] **Step 5: Actualizar `CreateCourseHandler`**

Reemplazar el contenido completo de `modules/Academic/Application/UseCases/CreateCourseHandler.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Exceptions\CourseCodeAlreadyExists;
use Modules\Academic\Application\Responses\CreateCourseResponse;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;

final readonly class CreateCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        CreateCourseCommand $command,
    ): CreateCourseResponse {
        $code = CourseCode::fromString($command->code);

        if ($this->courses->existsByCode($code)) {
            throw CourseCodeAlreadyExists::forCode($code);
        }

        $course = Course::create(
            id: CourseId::fromString((string) Str::uuid()),
            code: $code,
            title: CourseTitle::fromString($command->title),
            description: $command->description,
            objectives: $command->objectives,
            prerequisites: $command->prerequisites,
            modality: $command->modality === null ? null : CourseModality::from($command->modality),
            durationHours: $command->durationHours,
        );

        $this->courses->save($course);

        return CreateCourseResponse::fromCourse($course);
    }
}
```

- [ ] **Step 6: Actualizar `CreateCourseResponse` y `CourseListItemResponse`**

Reemplazar el contenido completo de `modules/Academic/Application/Responses/CreateCourseResponse.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class CreateCourseResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public ?string $description,
        public ?string $objectives,
        public ?string $prerequisites,
        public ?string $modality,
        public ?int $durationHours,
        public string $status,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            description: $course->description(),
            objectives: $course->objectives(),
            prerequisites: $course->prerequisites(),
            modality: $course->modality()?->value,
            durationHours: $course->durationHours(),
            status: $course->status()->value,
        );
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     description: string|null,
     *     objectives: string|null,
     *     prerequisites: string|null,
     *     modality: string|null,
     *     duration_hours: int|null,
     *     status: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'prerequisites' => $this->prerequisites,
            'modality' => $this->modality,
            'duration_hours' => $this->durationHours,
            'status' => $this->status,
        ];
    }
}
```

Reemplazar el contenido completo de `modules/Academic/Application/Responses/CourseListItemResponse.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class CourseListItemResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public ?string $description,
        public ?string $objectives,
        public ?string $prerequisites,
        public ?string $modality,
        public ?int $durationHours,
        public string $status,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            description: $course->description(),
            objectives: $course->objectives(),
            prerequisites: $course->prerequisites(),
            modality: $course->modality()?->value,
            durationHours: $course->durationHours(),
            status: $course->status()->value,
        );
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     description: string|null,
     *     objectives: string|null,
     *     prerequisites: string|null,
     *     modality: string|null,
     *     duration_hours: int|null,
     *     status: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'prerequisites' => $this->prerequisites,
            'modality' => $this->modality,
            'duration_hours' => $this->durationHours,
            'status' => $this->status,
        ];
    }
}
```

- [ ] **Step 7: Actualizar `CreateCourseRequest`**

Reemplazar el contenido completo de `modules/Academic/Presentation/Http/Requests/CreateCourseRequest.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\CourseModality;

final class CreateCourseRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
            ],
            'title' => [
                'required',
                'string',
                'max:180',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'objectives' => [
                'nullable',
                'string',
            ],
            'prerequisites' => [
                'nullable',
                'string',
            ],
            'modality' => [
                'nullable',
                'string',
                new Enum(CourseModality::class),
            ],
            'duration_hours' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del curso es obligatorio.',
            'code.regex' => 'El código solo puede contener letras, números y guiones intermedios.',
            'code.max' => 'El código no puede superar 50 caracteres.',
            'title.required' => 'El título del curso es obligatorio.',
            'title.max' => 'El título no puede superar 180 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'objectives.string' => 'Los objetivos deben ser texto.',
            'prerequisites.string' => 'Los requisitos deben ser texto.',
            'modality.enum' => 'La modalidad del curso no es válida.',
            'duration_hours.integer' => 'La duración debe ser un número entero de horas.',
            'duration_hours.min' => 'La duración debe ser de al menos 1 hora.',
        ];
    }
}
```

Nota: `CourseController::store()` ya lee `$validated['description']` con `isset(...)`; hay que agregar la misma lectura para los 4 campos nuevos. Ver Step 8.

- [ ] **Step 8: Actualizar `CourseController::store()` para pasar los campos nuevos**

Modificar `modules/Academic/Presentation/Http/Controllers/CourseController.php`, reemplazar el método `store()` completo:
```php
    public function store(
        CreateCourseRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new CreateCourseCommand(
                code: (string) $validated['code'],
                title: (string) $validated['title'],
                description: isset($validated['description'])
                    ? (string) $validated['description']
                    : null,
                objectives: isset($validated['objectives'])
                    ? (string) $validated['objectives']
                    : null,
                prerequisites: isset($validated['prerequisites'])
                    ? (string) $validated['prerequisites']
                    : null,
                modality: isset($validated['modality'])
                    ? (string) $validated['modality']
                    : null,
                durationHours: isset($validated['duration_hours'])
                    ? (int) $validated['duration_hours']
                    : null,
            ),
        );

        assert($result instanceof CreateCourseResponse);

        return response()->json(
            [
                'data' => $result->toArray(),
            ],
            Response::HTTP_CREATED,
        );
    }
```

- [ ] **Step 9: Proteger las rutas existentes con `auth:sanctum` + permisos**

Reemplazar el contenido completo de `modules/Academic/Presentation/Routes/api.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\AcademicStatusController;
use Modules\Academic\Presentation\Http\Controllers\CourseController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::get('/status', AcademicStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::middleware('permission:courses.view')->group(function (): void {
                Route::get('/courses', [CourseController::class, 'index'])
                    ->name('courses.index');
            });

            Route::middleware('permission:courses.manage')->group(function (): void {
                Route::post('/courses', [CourseController::class, 'store'])
                    ->name('courses.store');
            });
        });
    });
```

- [ ] **Step 10: Correr las pruebas de la Tarea 3 y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/CreateCourseTest.php modules/Academic/Tests/Feature/ListCoursesTest.php
```
Expected: PASS.

- [ ] **Step 11: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos composer quality
```
Expected: PASS.

- [ ] **Step 12: Commit**

```bash
git add modules/Academic/Application/Commands/CreateCourseCommand.php modules/Academic/Application/UseCases/CreateCourseHandler.php modules/Academic/Application/Responses/CreateCourseResponse.php modules/Academic/Application/Responses/CourseListItemResponse.php modules/Academic/Presentation/Http/Requests/CreateCourseRequest.php modules/Academic/Presentation/Http/Controllers/CourseController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Domain/Exceptions/CourseAlreadyPublished.php modules/Academic/Domain/Exceptions/CourseAlreadyArchived.php modules/Academic/Domain/Exceptions/ArchivedCourseCannotBeModified.php modules/Academic/Application/Exceptions/CourseCodeAlreadyExists.php modules/Academic/Tests/Feature/CreateCourseTest.php modules/Academic/Tests/Feature/ListCoursesTest.php
git commit -m "feat(academic): require authentication/permissions on course endpoints, expose new fields, fix exception handling"
```

---

### Task 4: Publicar un curso

**Files:**
- Create: `modules/Academic/Application/Commands/PublishCourseCommand.php`
- Create: `modules/Academic/Application/Responses/PublishCourseResponse.php`
- Create: `modules/Academic/Application/UseCases/PublishCourseHandler.php`
- Create: `modules/Academic/Application/Exceptions/CourseNotFound.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/CourseController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Feature/PublishCourseTest.php`

**Interfaces:**
- Consumes: `Course::publish(DateTimeImmutable): void` (ya existe, Tarea 1 no lo modifica), `CourseRepository::findById(CourseId): ?Course` (ya existe), grupo de rutas `auth:sanctum` de la Tarea 3.
- Produces: `CourseNotFound::withId(string): self` (`Modules\Academic\Application\Exceptions`, `statusCode: 404`, `errorCode: 'COURSE_NOT_FOUND'`) — la Tarea 5 (archivar) reusa esta misma excepción, no crea una propia.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Crear `modules/Academic/Tests/Feature/PublishCourseTest.php`:
```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\postJson;

use Tests\TestCase;

function createDraftCourseForPublishing(string $code = 'EDU-020'): Course
{
    $repository = app(CourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString('Curso de prueba'),
    );

    $repository->save($course);

    return $course;
}

it('publica un curso en borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing();

    $response = postJson("/api/v1/academic/courses/{$course->id()->value()}/publish");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'published');

    $stored = app(CourseRepository::class)->findById($course->id());

    expect($stored?->status()->value)->toBe('published')
        ->and($stored?->publishedAt())->not->toBeNull();
});

it('rechaza publicar un curso que ya está publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-021');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_ALREADY_PUBLISHED');
});

it('rechaza publicar un curso archivado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-023');
    $repository = app(CourseRepository::class);

    $course->archive(new DateTimeImmutable);
    $repository->save($course);

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'ARCHIVED_COURSE_CANNOT_BE_MODIFIED');
});

it('rechaza publicar un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/publish')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza publicar sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-022');

    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertForbidden();
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/PublishCourseTest.php
```
Expected: FAIL (404, la ruta no existe todavía).

- [ ] **Step 3: Crear `CourseNotFound`**

Crear `modules/Academic/Application/Exceptions/CourseNotFound.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class CourseNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe un curso con el identificador %s.', $id),
            errorCode: 'COURSE_NOT_FOUND',
            statusCode: 404,
        );
    }
}
```

- [ ] **Step 4: Crear `PublishCourseCommand` y `PublishCourseResponse`**

Crear `modules/Academic/Application/Commands/PublishCourseCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class PublishCourseCommand implements Command
{
    public function __construct(
        public string $courseId,
    ) {}
}
```

Crear `modules/Academic/Application/Responses/PublishCourseResponse.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class PublishCourseResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
        public string $publishedAt,
    ) {}

    public static function fromCourse(Course $course): self
    {
        $publishedAt = $course->publishedAt();

        assert($publishedAt !== null);

        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
            publishedAt: $publishedAt->format(DATE_ATOM),
        );
    }

    /**
     * @return array{id: string, code: string, title: string, status: string, published_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
        ];
    }
}
```

- [ ] **Step 5: Crear `PublishCourseHandler`**

Crear `modules/Academic/Application/UseCases/PublishCourseHandler.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\PublishCourseResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class PublishCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        PublishCourseCommand $command,
    ): PublishCourseResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->findById($courseId);

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $course->publish(new DateTimeImmutable);

        $this->courses->save($course);

        return PublishCourseResponse::fromCourse($course);
    }
}
```

- [ ] **Step 6: Agregar `CourseController::publish()`**

Modificar `modules/Academic/Presentation/Http/Controllers/CourseController.php`: agregar los imports `use Modules\Academic\Application\Commands\PublishCourseCommand;` y `use Modules\Academic\Application\Responses\PublishCourseResponse;` junto a los demás `use`, y agregar este método dentro de la clase (después de `store()`):
```php
    public function publish(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new PublishCourseCommand(courseId: $courseId),
        );

        assert($result instanceof PublishCourseResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }
```

- [ ] **Step 7: Agregar la ruta de publicar**

Modificar `modules/Academic/Presentation/Routes/api.php`: dentro del grupo `Route::middleware('permission:courses.manage')->group(function (): void { ... })`, después de la ruta `courses.store`, agregar:
```php
            Route::post('/courses/{courseId}/publish', [CourseController::class, 'publish'])
                ->whereUuid('courseId')
                ->name('courses.publish');
```

- [ ] **Step 8: Registrar el handler en `AcademicServiceProvider`**

Modificar `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`: agregar los imports `use Modules\Academic\Application\Commands\PublishCourseCommand;` y `use Modules\Academic\Application\UseCases\PublishCourseHandler;`, y dentro de `boot()`, después del registro de `CreateCourseCommand`, agregar:
```php
        $registry->register(
            PublishCourseCommand::class,
            PublishCourseHandler::class,
        );
```

- [ ] **Step 9: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/PublishCourseTest.php
```
Expected: PASS.

- [ ] **Step 10: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos composer quality
```
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add modules/Academic/Application/Commands/PublishCourseCommand.php modules/Academic/Application/Responses/PublishCourseResponse.php modules/Academic/Application/UseCases/PublishCourseHandler.php modules/Academic/Application/Exceptions/CourseNotFound.php modules/Academic/Presentation/Http/Controllers/CourseController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Feature/PublishCourseTest.php
git commit -m "feat(academic): add publish course endpoint"
```

---

### Task 5: Archivar un curso

**Files:**
- Create: `modules/Academic/Application/Commands/ArchiveCourseCommand.php`
- Create: `modules/Academic/Application/Responses/ArchiveCourseResponse.php`
- Create: `modules/Academic/Application/UseCases/ArchiveCourseHandler.php`
- Modify: `modules/Academic/Presentation/Http/Controllers/CourseController.php`
- Modify: `modules/Academic/Presentation/Routes/api.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Test: `modules/Academic/Tests/Feature/ArchiveCourseTest.php`

**Interfaces:**
- Consumes: `Course::archive(DateTimeImmutable): void` (ya existe), `CourseRepository::findById()` (ya existe), `CourseNotFound::withId()` (Tarea 4), helper de test `createDraftCourseForPublishing()` (Tarea 4, definido en `PublishCourseTest.php` — reusable porque Pest carga todos los archivos de test en el mismo proceso; no se duplica).
- Produces: endpoint `POST /api/v1/academic/courses/{courseId}/archive`.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Crear `modules/Academic/Tests/Feature/ArchiveCourseTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\postJson;

use Tests\TestCase;

it('archiva un curso en borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-030');

    $response = postJson("/api/v1/academic/courses/{$course->id()->value()}/archive");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'archived');

    $stored = app(CourseRepository::class)->findById($course->id());

    expect($stored?->status()->value)->toBe('archived')
        ->and($stored?->archivedAt())->not->toBeNull();
});

it('archiva un curso publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-031');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('rechaza archivar un curso que ya está archivado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-032');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_ALREADY_ARCHIVED');
});

it('rechaza archivar un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/archive')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza archivar sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-033');

    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertForbidden();
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/ArchiveCourseTest.php
```
Expected: FAIL (404, la ruta no existe todavía).

- [ ] **Step 3: Crear `ArchiveCourseCommand` y `ArchiveCourseResponse`**

Crear `modules/Academic/Application/Commands/ArchiveCourseCommand.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ArchiveCourseCommand implements Command
{
    public function __construct(
        public string $courseId,
    ) {}
}
```

Crear `modules/Academic/Application/Responses/ArchiveCourseResponse.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class ArchiveCourseResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
        public string $archivedAt,
    ) {}

    public static function fromCourse(Course $course): self
    {
        $archivedAt = $course->archivedAt();

        assert($archivedAt !== null);

        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
            archivedAt: $archivedAt->format(DATE_ATOM),
        );
    }

    /**
     * @return array{id: string, code: string, title: string, status: string, archived_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'archived_at' => $this->archivedAt,
        ];
    }
}
```

- [ ] **Step 4: Crear `ArchiveCourseHandler`**

Crear `modules/Academic/Application/UseCases/ArchiveCourseHandler.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Responses\ArchiveCourseResponse;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ArchiveCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
    ) {}

    public function handle(
        ArchiveCourseCommand $command,
    ): ArchiveCourseResponse {
        $courseId = CourseId::fromString($command->courseId);

        $course = $this->courses->findById($courseId);

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        $course->archive(new DateTimeImmutable);

        $this->courses->save($course);

        return ArchiveCourseResponse::fromCourse($course);
    }
}
```

- [ ] **Step 5: Agregar `CourseController::archive()`**

Modificar `modules/Academic/Presentation/Http/Controllers/CourseController.php`: agregar los imports `use Modules\Academic\Application\Commands\ArchiveCourseCommand;` y `use Modules\Academic\Application\Responses\ArchiveCourseResponse;`, y agregar este método (después de `publish()`):
```php
    public function archive(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new ArchiveCourseCommand(courseId: $courseId),
        );

        assert($result instanceof ArchiveCourseResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }
```

- [ ] **Step 6: Agregar la ruta de archivar**

Modificar `modules/Academic/Presentation/Routes/api.php`: dentro del mismo grupo `permission:courses.manage`, después de la ruta `courses.publish`, agregar:
```php
            Route::post('/courses/{courseId}/archive', [CourseController::class, 'archive'])
                ->whereUuid('courseId')
                ->name('courses.archive');
```

- [ ] **Step 7: Registrar el handler en `AcademicServiceProvider`**

Modificar `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`: agregar los imports `use Modules\Academic\Application\Commands\ArchiveCourseCommand;` y `use Modules\Academic\Application\UseCases\ArchiveCourseHandler;`, y dentro de `boot()`, después del registro de `PublishCourseCommand`, agregar:
```php
        $registry->register(
            ArchiveCourseCommand::class,
            ArchiveCourseHandler::class,
        );
```

- [ ] **Step 8: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos php artisan test modules/Academic/Tests/Feature/ArchiveCourseTest.php
```
Expected: PASS.

- [ ] **Step 9: Correr `composer quality` completo (suite entera)**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-academic-cursos composer quality
```
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add modules/Academic/Application/Commands/ArchiveCourseCommand.php modules/Academic/Application/Responses/ArchiveCourseResponse.php modules/Academic/Application/UseCases/ArchiveCourseHandler.php modules/Academic/Presentation/Http/Controllers/CourseController.php modules/Academic/Presentation/Routes/api.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php modules/Academic/Tests/Feature/ArchiveCourseTest.php
git commit -m "feat(academic): add archive course endpoint"
```

---

## Fuera de alcance (no hacer en este plan)

- Sistema de versionado/historial curricular real (ENG-029) — se difiere como su propia historia futura.
- Endpoint de edición general de un curso ya existente (título, descripción, objetivos, requisitos, modalidad, duración) — no se agregan mutadores de dominio nuevos ni endpoint `PATCH`.
- ENG-024 (catálogo de competencias), ENG-025 (programas educativos), ENG-027 (módulos y unidades), ENG-028 (lecciones) — historias separadas.
- Contexto organizacional en los permisos de cursos (siguen siendo globales por usuario, igual que hoy son los de Organización).
