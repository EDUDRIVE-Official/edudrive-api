# Panel web de Cursos (listar, crear, publicar, archivar) — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir un panel web para Cursos (listar, crear, publicar, archivar), con el mismo nivel de funcionalidad que ya tiene Organizaciones más las dos acciones de estado que Cursos sí tiene, reusando el backend y los componentes de UI ya existentes.

**Architecture:** Nuevo `CourseWebController` (junto al `CourseController` de API) reusa exactamente el mismo `CommandBus`/`QueryBus` y los mismos comandos/requests ya existentes de la API de Cursos — solo cambia la capa de presentación. Las excepciones de dominio de `publish`/`archive` (ya corregidas en IMP-024 para extender `Modules\Foundation\Domain\Exceptions\DomainException`) se capturan explícitamente en el controlador web y se convierten en un mensaje flash, porque el manejador genérico de excepciones en `bootstrap/app.php` solo produce JSON para peticiones `api/*`/`expectsJson()` — para una petición web caería en un 500 sin este manejo explícito. `CourseModality`/`CourseStatus` ganan un método `label()` para mostrar texto legible en español, evitando el defecto que la revisión final del panel de Organizaciones encontró (un enum mostrado crudo en una vista).

**Tech Stack:** Laravel 12, Blade components, guard de sesión `web` (ya configurado), Pest.

## Global Constraints

- PHP 8.4 / Laravel 12, sin cambios de versión ni de dependencias.
- `declare(strict_types=1);` en todo archivo PHP nuevo.
- Todos los comandos de PHP/Composer se ejecutan vía Docker (ver "Convenciones" abajo).
- `composer quality` (Pint check + Larastan/PHPStan + Pest completo) en verde después de cada tarea.
- No se modifica ningún archivo de `Modules\Academic\Domain`/`Application` de creación/listado/publicar/archivar ya existente (`CreateCourseCommand`, `CreateCourseHandler`, `PublishCourseCommand`, `ArchiveCourseCommand`, sus handlers, `CourseController` de API) — se reusan tal cual. Los únicos cambios de dominio son los métodos `label()` (Tarea 1), puramente aditivos.
- **Lección de la historia anterior (panel de Organizaciones) — obligatoria para todo test de ruta web en este plan**: los helpers `actingAsAuthenticatedUser()`/`actingAsSuperAdminUser()` (en `tests/Pest.php`) llaman internamente a `Sanctum::actingAs()`, lo que fija el guard de autenticación por defecto en `sanctum`. Para autenticar una prueba de ruta **web** (sesión), hay que llamar explícitamente `$this->actingAs($user, 'web')` después — **nunca** `$this->actingAs($user)` sin el segundo argumento, porque no cambiaría el guard real y la prueba pasaría por la razón equivocada. Todos los tests de este plan siguen esta regla.
- **Otra lección de la historia anterior**: si una vista referencia una ruta con nombre que otra tarea todavía no registró, y el propio test de la tarea actual renderiza esa rama (por ejemplo, un botón visible para un superadministrador), el `route()` sin resolver rompe con `RouteNotFoundException`. Este plan ordena las tareas específicamente para evitar eso: el enlace "Nuevo curso" se agrega recién en la Tarea 4 (cuando `courses.create` ya existe), los botones de publicar/archivar recién en la Tarea 5, y los enlaces de navegación de la topbar recién en la Tarea 3 (después de que tanto `organizations.index` como `courses.index` ya existan).
- Diseño aprobado: `docs/plans/2026-08-02-panel-cursos-web-design.md`.

---

## Convenciones usadas en este plan (leer una vez)

- Todos los comandos PHP/Composer se ejecutan vía Docker:
  ```bash
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web <comando>
  ```
  Si la imagen no existe todavía en el worktree, construirla una vez antes de la Tarea 1:
  ```bash
  MSYS_NO_PATHCONV=1 docker build -t edudrive-worktree-cursos-web -f docker/php/Dockerfile .
  ```
  Después, dentro del worktree: `composer install`, `cp .env.example .env && php artisan key:generate`, y `npm install && npm run build` (el manifest de Vite no existe hasta correr esto una vez — las páginas que usan `@vite` fallan sin él).
- Las pruebas usan SQLite en memoria (`phpunit.xml`, `RefreshDatabase`) — no requieren PostgreSQL/Redis reales.
- Después de **cada** tarea: correr `composer quality` y confirmar verde antes de comitear.

---

### Task 1: Etiquetas legibles para `CourseModality` y `CourseStatus`

**Files:**
- Modify: `modules/Academic/Domain/Enums/CourseModality.php`
- Modify: `modules/Academic/Domain/Enums/CourseStatus.php`
- Test: `modules/Academic/Tests/Unit/Domain/Enums/CourseModalityTest.php` (nuevo)
- Test: `modules/Academic/Tests/Unit/Domain/Enums/CourseStatusTest.php` (nuevo)

**Interfaces:**
- Consumes: nada nuevo.
- Produces: `CourseModality::label(): string` (`InPerson`→`'Presencial'`, `Virtual`→`'Virtual'`, `Hybrid`→`'Híbrida'`); `CourseStatus::label(): string` (`Draft`→`'Borrador'`, `Published`→`'Publicado'`, `Archived`→`'Archivado'`). Las Tareas 2, 4 y 5 consumen ambos métodos desde las vistas Blade.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Crear `modules/Academic/Tests/Unit/Domain/Enums/CourseModalityTest.php`:
```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\Enums\CourseModality;

it('devuelve la etiqueta legible de cada modalidad', function (): void {
    expect(CourseModality::InPerson->label())->toBe('Presencial')
        ->and(CourseModality::Virtual->label())->toBe('Virtual')
        ->and(CourseModality::Hybrid->label())->toBe('Híbrida');
});
```

Crear `modules/Academic/Tests/Unit/Domain/Enums/CourseStatusTest.php`:
```php
<?php

declare(strict_types=1);

use Modules\Academic\Domain\Enums\CourseStatus;

it('devuelve la etiqueta legible de cada estado', function (): void {
    expect(CourseStatus::Draft->label())->toBe('Borrador')
        ->and(CourseStatus::Published->label())->toBe('Publicado')
        ->and(CourseStatus::Archived->label())->toBe('Archivado');
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Unit/Domain/Enums/CourseModalityTest.php modules/Academic/Tests/Unit/Domain/Enums/CourseStatusTest.php
```
Expected: FAIL (`label()` no existe todavía en ninguno de los dos enums).

- [ ] **Step 3: Agregar `label()` a `CourseModality`**

Reemplazar el contenido completo de `modules/Academic/Domain/Enums/CourseModality.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseModality: string
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::Virtual => 'Virtual',
            self::Hybrid => 'Híbrida',
        };
    }
}
```

- [ ] **Step 4: Agregar `label()` a `CourseStatus`**

Reemplazar el contenido completo de `modules/Academic/Domain/Enums/CourseStatus.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }
}
```

- [ ] **Step 5: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Unit/Domain/Enums/CourseModalityTest.php modules/Academic/Tests/Unit/Domain/Enums/CourseStatusTest.php
```
Expected: PASS.

- [ ] **Step 6: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add modules/Academic/Domain/Enums/CourseModality.php modules/Academic/Domain/Enums/CourseStatus.php modules/Academic/Tests/Unit/Domain/Enums/CourseModalityTest.php modules/Academic/Tests/Unit/Domain/Enums/CourseStatusTest.php
git commit -m "feat(academic): add label() to CourseModality and CourseStatus"
```

---

### Task 2: Listar cursos (web)

**Files:**
- Create: `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`
- Create: `modules/Academic/Presentation/Routes/web.php`
- Modify: `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`
- Create: `resources/views/courses/index.blade.php`
- Test: `modules/Academic/Tests/Feature/CoursesIndexWebTest.php`

**Interfaces:**
- Consumes: `QueryBus::ask(ListCoursesQuery): array` de `list<CourseListItemResponse>`, cada uno con `toArray(): array{id, code, title, description, objectives, prerequisites, modality, duration_hours, status}` (ya existente); `PermissionChecker::userHasPermission(string, Permission): bool`; `Permission::ViewCourses`/`ManageCourses` (ya existentes); `CourseModality::label()`/`CourseStatus::label()` (Tarea 1); `<x-layouts.app>`, `<x-ui.table>`, `<x-ui.badge>` (ya existentes).
- Produces: ruta nombrada `courses.index` en `GET /courses`, protegida por `auth` + `permission:courses.view`; vista `courses.index` que recibe `courses` (array de arrays) y `canManage` (bool) — `canManage` no se usa todavía en esta tarea (no hay botones que mostrar aún), pero la Tarea 4 lo consume para el botón "Nuevo curso" y la Tarea 5 para publicar/archivar.

- [ ] **Step 1: Escribir el test que debe fallar**

Crear `modules/Academic/Tests/Feature/CoursesIndexWebTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
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

use Tests\TestCase;

it('redirige a un invitado que intenta ver la lista de cursos', function (): void {
    /** @var TestCase $this */
    $this->get('/courses')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin ninguna asignación de rol', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user, 'web');

    $this->get('/courses')->assertForbidden();
});

it('muestra la lista con etiquetas legibles a un usuario con permiso de vista', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
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

    $courses = app(CourseRepository::class);
    $courses->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EDU-100'),
        title: CourseTitle::fromString('Curso Virtual de Prueba'),
        modality: CourseModality::Virtual,
        durationHours: 12,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSeeText('Curso Virtual de Prueba');
    $response->assertSeeText('Virtual');
    $response->assertSeeText('Borrador');
});
```

- [ ] **Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: FAIL (404, la ruta no existe todavía).

- [ ] **Step 3: Crear el controlador**

Crear `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;

final class CourseWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(
            new ListCoursesQuery,
        );

        assert(is_array($result));

        /** @var list<CourseListItemResponse> $result */
        $courses = array_map(
            static fn (CourseListItemResponse $course): array => $course->toArray(),
            $result,
        );

        return view('courses.index', [
            'courses' => $courses,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageCourses,
            ),
        ]);
    }
}
```

- [ ] **Step 4: Crear la vista**

Crear `resources/views/courses/index.blade.php`:
```blade
<x-layouts.app title="EDUDRIVE — Cursos">
    <div class="flex flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Cursos</h1>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Código</th>
                    <th scope="col" class="px-4 py-2">Título</th>
                    <th scope="col" class="px-4 py-2">Modalidad</th>
                    <th scope="col" class="px-4 py-2">Duración</th>
                    <th scope="col" class="px-4 py-2">Estado</th>
                </tr>
            </x-slot:head>
            @forelse ($courses as $course)
                <tr>
                    <td class="px-4 py-2">{{ $course['code'] }}</td>
                    <td class="px-4 py-2">{{ $course['title'] }}</td>
                    <td class="px-4 py-2">
                        {{ $course['modality'] ? \Modules\Academic\Domain\Enums\CourseModality::from($course['modality'])->label() : '—' }}
                    </td>
                    <td class="px-4 py-2">{{ $course['duration_hours'] ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @php
                            $status = \Modules\Academic\Domain\Enums\CourseStatus::from($course['status']);
                            $statusVariant = match ($status) {
                                \Modules\Academic\Domain\Enums\CourseStatus::Draft => 'warning',
                                \Modules\Academic\Domain\Enums\CourseStatus::Published => 'success',
                                \Modules\Academic\Domain\Enums\CourseStatus::Archived => 'danger',
                            };
                        @endphp
                        <x-ui.badge :variant="$statusVariant">{{ $status->label() }}</x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="5">Todavía no hay cursos registrados.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Crear las rutas web de Academic**

Crear `modules/Academic/Presentation/Routes/web.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');
    });
});
```

- [ ] **Step 6: Registrar las rutas en `AcademicServiceProvider`**

Modificar `modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php`: en `boot()`, después de `$this->loadRoutesFrom(dirname(__DIR__, 2).'/Presentation/Routes/api.php');`, agregar:
```php
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/web.php',
        );
```

- [ ] **Step 7: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: PASS.

- [ ] **Step 8: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/CourseWebController.php modules/Academic/Presentation/Routes/web.php modules/Academic/Infrastructure/Providers/AcademicServiceProvider.php resources/views/courses/index.blade.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
git commit -m "feat(academic): add web courses list"
```

---

### Task 3: Navegación entre Organizaciones y Cursos en la topbar

**Files:**
- Modify: `resources/views/components/layouts/app.blade.php`

Sin test propio (es Blade puro, mismo criterio que el resto del layout) — se verifica corriendo la suite completa, ya que muchos tests existentes (login, Organizaciones, Cursos, páginas de error) renderizan esta topbar autenticada.

**Interfaces:**
- Consumes: rutas nombradas `organizations.index` (ya existente) y `courses.index` (Tarea 2, ya registrada) — por eso esta tarea va después de la Tarea 2, no antes.
- Produces: dos enlaces de texto en la topbar, visibles solo con sesión iniciada.

- [ ] **Step 1: Agregar los enlaces de navegación**

Modificar `resources/views/components/layouts/app.blade.php`: reemplazar esta línea:
```blade
            <span class="font-heading text-lg font-bold text-text">EDUDRIVE</span>
```
por:
```blade
            <div class="flex items-center gap-6">
                <span class="font-heading text-lg font-bold text-text">EDUDRIVE</span>
                @auth
                    <nav class="flex items-center gap-4">
                        <a href="{{ route('organizations.index') }}" class="font-sans text-sm text-text hover:text-primary">Organizaciones</a>
                        <a href="{{ route('courses.index') }}" class="font-sans text-sm text-text hover:text-primary">Cursos</a>
                    </nav>
                @endauth
            </div>
```

- [ ] **Step 2: Correr la suite completa y confirmar que nada se rompió**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS. Presta atención especial a que ningún test existente que renderice la topbar autenticada (login exitoso, páginas de Organizaciones, la nueva página de Cursos, la página de error 403) falle por `RouteNotFoundException` — si eso pasara, confirmaría que `courses.index`/`organizations.index` no están disponibles todavía, lo cual no debería ocurrir en este punto del plan.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/layouts/app.blade.php
git commit -m "feat(web): add navigation links between organizations and courses"
```

---

### Task 4: Crear un curso (web)

**Files:**
- Modify: `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`
- Modify: `modules/Academic/Presentation/Routes/web.php`
- Create: `resources/views/courses/create.blade.php`
- Modify: `resources/views/courses/index.blade.php`
- Test: `modules/Academic/Tests/Feature/CoursesCreateWebTest.php`
- Test: `modules/Academic/Tests/Feature/CoursesIndexWebTest.php` (agregar un caso)

**Interfaces:**
- Consumes: `CreateCourseRequest` (ya existente, valida `code`/`title` obligatorios y `objectives`/`prerequisites`/`modality`/`duration_hours` opcionales), `CreateCourseCommand`/`CommandBus::dispatch()` (ya existentes), `CourseModality::cases()`/`label()` (Tarea 1).
- Produces: rutas `courses.create` (GET) y `courses.store` (POST), ambas bajo `permission:courses.manage`; botón "Nuevo curso" en la lista, visible solo si `canManage`.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Crear `modules/Academic/Tests/Feature/CoursesCreateWebTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\assertDatabaseHas;

use Tests\TestCase;

it('rechaza a un usuario con solo permiso de vista', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
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

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/courses/create')->assertForbidden();
    $this->post('/courses', ['code' => 'EDU-200', 'title' => 'Curso X'])->assertForbidden();
});

it('muestra el formulario de creación a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $this->get('/courses/create')
        ->assertOk()
        ->assertSeeText('Nuevo curso');
});

it('crea un curso con todos los campos y redirige a la lista con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', [
        'code' => 'EDU-201',
        'title' => 'Manejo Defensivo Web',
        'description' => 'Curso completo de manejo defensivo.',
        'objectives' => 'Aplicar técnicas de manejo defensivo.',
        'prerequisites' => 'Licencia vigente.',
        'modality' => 'hybrid',
        'duration_hours' => 18,
    ]);

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('status');

    assertDatabaseHas('academic_courses', [
        'code' => 'EDU-201',
        'title' => 'Manejo Defensivo Web',
        'modality' => 'hybrid',
        'duration_hours' => 18,
    ]);
});

it('vuelve al formulario con errores cuando faltan datos obligatorios', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['code', 'title']);
});

it('vuelve al formulario con error cuando la modalidad es inválida', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->post('/courses', [
        'code' => 'EDU-202',
        'title' => 'Curso con modalidad inválida',
        'modality' => 'no-existe',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['modality']);
});
```

Agregar a `modules/Academic/Tests/Feature/CoursesIndexWebTest.php` (al final del archivo):
```php
it('muestra el botón "Nuevo curso" a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSee('href="'.route('courses.create').'"', false);
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesCreateWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: FAIL (`courses.create`/`courses.store` no existen todavía).

- [ ] **Step 3: Agregar `create()`/`store()` al controlador**

Modificar `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`: agregar los imports `use Illuminate\Http\RedirectResponse;`, `use Modules\Academic\Application\Commands\CreateCourseCommand;`, `use Modules\Academic\Domain\Enums\CourseModality;`, `use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;`, `use Modules\Foundation\Application\Bus\CommandBus;`, y agregar estos métodos dentro de la clase (después de `index()`):
```php
    public function create(): View
    {
        return view('courses.create', [
            'modalities' => CourseModality::cases(),
        ]);
    }

    public function store(
        CreateCourseRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $validated = $request->validated();

        $commandBus->dispatch(
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

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso creado correctamente.');
    }
```

- [ ] **Step 4: Crear la vista de creación**

Crear `resources/views/courses/create.blade.php`:
```blade
<x-layouts.app title="EDUDRIVE — Nuevo curso">
    <div class="mx-auto flex max-w-xl flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Nuevo curso</h1>

        <x-ui.card>
            <form method="POST" action="{{ route('courses.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="code"
                    label="Código"
                    value="{{ old('code') }}"
                    :error="$errors->first('code')"
                />

                <x-ui.input
                    name="title"
                    label="Título"
                    value="{{ old('title') }}"
                    :error="$errors->first('title')"
                />

                <div class="flex flex-col gap-1">
                    <label for="description" class="font-sans text-sm font-medium text-text">Descripción</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="objectives" class="font-sans text-sm font-medium text-text">Objetivos</label>
                    <textarea
                        id="objectives"
                        name="objectives"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('objectives') }}</textarea>
                    @error('objectives')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="prerequisites" class="font-sans text-sm font-medium text-text">Requisitos</label>
                    <textarea
                        id="prerequisites"
                        name="prerequisites"
                        rows="3"
                        class="rounded-sm border border-border bg-surface px-3 py-2 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >{{ old('prerequisites') }}</textarea>
                    @error('prerequisites')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1">
                    <label for="modality" class="font-sans text-sm font-medium text-text">Modalidad</label>
                    <select
                        id="modality"
                        name="modality"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        <option value="" @selected(old('modality') === null)>Sin especificar</option>
                        @foreach ($modalities as $modality)
                            <option value="{{ $modality->value }}" @selected(old('modality') === $modality->value)>
                                {{ $modality->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('modality')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input
                    name="duration_hours"
                    type="number"
                    label="Duración (horas)"
                    value="{{ old('duration_hours') }}"
                    :error="$errors->first('duration_hours')"
                />

                <x-ui.button type="submit" variant="primary">Crear</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
```

Nota: un envío con el campo `modality` vacío (`""`, la opción "Sin especificar") llega al servidor ya convertido a `null` por el middleware global `ConvertEmptyStringsToNull` (ya activo en toda la aplicación), por lo que la regla `nullable` de `CreateCourseRequest` lo acepta sin pasar por la validación de `Enum` — no hace falta ningún manejo especial en el controlador.

- [ ] **Step 5: Agregar el botón "Nuevo curso" a la lista**

Modificar `resources/views/courses/index.blade.php`: reemplazar
```blade
        <h1 class="font-heading text-2xl font-bold">Cursos</h1>
```
por:
```blade
        <div class="flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">Cursos</h1>
            @if ($canManage)
                <a
                    href="{{ route('courses.create') }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-sm bg-primary px-4 font-sans font-medium text-white transition hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Nuevo curso
                </a>
            @endif
        </div>
```

- [ ] **Step 6: Agregar las rutas de creación**

Reemplazar el contenido completo de `modules/Academic/Presentation/Routes/web.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');
    });

    Route::middleware('permission:courses.manage')->group(function (): void {
        Route::get('/courses/create', [CourseWebController::class, 'create'])
            ->name('courses.create');

        Route::post('/courses', [CourseWebController::class, 'store'])
            ->name('courses.store');
    });
});
```

- [ ] **Step 7: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesCreateWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: PASS.

- [ ] **Step 8: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/CourseWebController.php modules/Academic/Presentation/Routes/web.php resources/views/courses/create.blade.php resources/views/courses/index.blade.php modules/Academic/Tests/Feature/CoursesCreateWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
git commit -m "feat(academic): add web course creation form"
```

---

### Task 5: Publicar y archivar un curso (web)

**Files:**
- Modify: `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`
- Modify: `modules/Academic/Presentation/Routes/web.php`
- Modify: `resources/views/courses/index.blade.php`
- Test: `modules/Academic/Tests/Feature/CoursesPublishArchiveWebTest.php`
- Test: `modules/Academic/Tests/Feature/CoursesIndexWebTest.php` (agregar un caso)

**Interfaces:**
- Consumes: `PublishCourseCommand`/`ArchiveCourseCommand` y sus handlers (ya existentes de la API), `Modules\Foundation\Domain\Exceptions\DomainException` (clase base ya usada por `CourseAlreadyPublished`/`CourseAlreadyArchived`/`ArchivedCourseCannotBeModified`/`CourseNotFound` desde IMP-024), helper de test `createDraftCourseForPublishing()` (ya existe en `tests/Pest.php`).
- Produces: rutas `courses.publish`/`courses.archive` (POST), ambas bajo `permission:courses.manage`; botones "Publicar"/"Archivar" por fila en la lista, condicionados a `canManage` y al estado del curso.

- [ ] **Step 1: Escribir las pruebas que deben fallar**

Crear `modules/Academic/Tests/Feature/CoursesPublishArchiveWebTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use Tests\TestCase;

it('publica un curso en borrador y redirige con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-300');

    $response = $this->post("/courses/{$course->id()->value()}/publish");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('status');

    $stored = app(CourseRepository::class)->findById($course->id());
    expect($stored?->status()->value)->toBe('published');
});

it('archiva un curso en borrador y redirige con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-301');

    $response = $this->post("/courses/{$course->id()->value()}/archive");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('status');

    $stored = app(CourseRepository::class)->findById($course->id());
    expect($stored?->status()->value)->toBe('archived');
});

it('redirige con un mensaje de error al publicar un curso ya publicado, sin romper con un 500', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-302');
    $this->post("/courses/{$course->id()->value()}/publish")->assertRedirect();

    $response = $this->post("/courses/{$course->id()->value()}/publish");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('error', 'El curso ya está publicado.');
});

it('redirige con un mensaje de error al archivar un curso ya archivado, sin romper con un 500', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-303');
    $this->post("/courses/{$course->id()->value()}/archive")->assertRedirect();

    $response = $this->post("/courses/{$course->id()->value()}/archive");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('error', 'El curso ya está archivado.');
});

it('rechaza publicar y archivar sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-304');

    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
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

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post("/courses/{$course->id()->value()}/publish")->assertForbidden();
    $this->post("/courses/{$course->id()->value()}/archive")->assertForbidden();
});
```

Agregar a `modules/Academic/Tests/Feature/CoursesIndexWebTest.php` (al final del archivo, y agregar los imports `use DateTimeImmutable;` y `use Modules\Academic\Domain\Repositories\CourseRepository;` junto a los demás si no están ya):
```php
it('muestra los botones de publicar y archivar según el estado del curso', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $draft = createDraftCourseForPublishing('EDU-305');

    $published = createDraftCourseForPublishing('EDU-306');
    $published->publish(new DateTimeImmutable);
    app(CourseRepository::class)->save($published);

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSee('action="'.route('courses.publish', $draft->id()->value()).'"', false);
    $response->assertSee('action="'.route('courses.archive', $draft->id()->value()).'"', false);
    $response->assertSee('action="'.route('courses.archive', $published->id()->value()).'"', false);
    $response->assertDontSee('action="'.route('courses.publish', $published->id()->value()).'"', false);
});
```

- [ ] **Step 2: Correr las pruebas y confirmar que fallan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesPublishArchiveWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: FAIL (404, `courses.publish`/`courses.archive` no existen todavía).

- [ ] **Step 3: Agregar `publish()`/`archive()` al controlador**

Modificar `modules/Academic/Presentation/Http/Controllers/CourseWebController.php`: agregar los imports `use Modules\Academic\Application\Commands\ArchiveCourseCommand;`, `use Modules\Academic\Application\Commands\PublishCourseCommand;`, `use Modules\Foundation\Domain\Exceptions\DomainException;`, y agregar estos métodos (después de `store()`):
```php
    public function publish(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new PublishCourseCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso publicado correctamente.');
    }

    public function archive(
        string $courseId,
        CommandBus $commandBus,
    ): RedirectResponse {
        try {
            $commandBus->dispatch(
                new ArchiveCourseCommand(courseId: $courseId),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('courses.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('courses.index')
            ->with('status', 'Curso archivado correctamente.');
    }
```

- [ ] **Step 4: Agregar las rutas de publicar/archivar**

Reemplazar el contenido completo de `modules/Academic/Presentation/Routes/web.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');
    });

    Route::middleware('permission:courses.manage')->group(function (): void {
        Route::get('/courses/create', [CourseWebController::class, 'create'])
            ->name('courses.create');

        Route::post('/courses', [CourseWebController::class, 'store'])
            ->name('courses.store');

        Route::post('/courses/{courseId}/publish', [CourseWebController::class, 'publish'])
            ->whereUuid('courseId')
            ->name('courses.publish');

        Route::post('/courses/{courseId}/archive', [CourseWebController::class, 'archive'])
            ->whereUuid('courseId')
            ->name('courses.archive');
    });
});
```

- [ ] **Step 5: Agregar los botones de acción y el mensaje de error a la lista**

Modificar `resources/views/courses/index.blade.php`:

Agregar, después de `@if (session('status')) ... @endif`:
```blade
        @if (session('error'))
            <p class="font-sans text-sm text-danger-text">{{ session('error') }}</p>
        @endif
```

Agregar una columna de encabezado "Acciones" al final de la fila `<x-slot:head>`:
```blade
                    <th scope="col" class="px-4 py-2">Acciones</th>
```

Cambiar `colspan="5"` a `colspan="6"` en la fila del estado vacío (`@empty`).

Agregar una celda de acciones al final de la fila `@forelse` (después de la celda de estado, antes de `</tr>`):
```blade
                    <td class="px-4 py-2">
                        @if ($canManage)
                            <div class="flex gap-2">
                                @if ($status === \Modules\Academic\Domain\Enums\CourseStatus::Draft)
                                    <form method="POST" action="{{ route('courses.publish', $course['id']) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary" size="sm">Publicar</x-ui.button>
                                    </form>
                                @endif
                                @if (! $status->isArchived())
                                    <form
                                        method="POST"
                                        action="{{ route('courses.archive', $course['id']) }}"
                                        onsubmit="return confirm('¿Seguro que querés archivar este curso? No se puede deshacer.');"
                                    >
                                        @csrf
                                        <x-ui.button type="submit" variant="danger" size="sm">Archivar</x-ui.button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </td>
```

- [ ] **Step 6: Correr las pruebas y confirmar que pasan**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web php artisan test modules/Academic/Tests/Feature/CoursesPublishArchiveWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
```
Expected: PASS.

- [ ] **Step 7: Correr `composer quality` completo (suite entera)**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add modules/Academic/Presentation/Http/Controllers/CourseWebController.php modules/Academic/Presentation/Routes/web.php resources/views/courses/index.blade.php modules/Academic/Tests/Feature/CoursesPublishArchiveWebTest.php modules/Academic/Tests/Feature/CoursesIndexWebTest.php
git commit -m "feat(academic): add web publish/archive actions for courses"
```

---

### Task 6: Verificación visual final (navegador)

**Files:** ninguno (solo verificación; posibles commits de corrección si algo se ve mal)

- [ ] **Step 1: Levantar el servidor de desarrollo**

Usar las herramientas de preview/browser del entorno. Este trabajo no tocó CSS/JS, no hace falta `npm run dev`/`build` adicional más allá del `npm run build` inicial del worktree.

- [ ] **Step 2: Preparar un usuario de prueba**

Si el usuario `abel@test.com` con rol `SuperAdmin` ya existe en la base real (creado durante la verificación visual del panel de Organizaciones), reusarlo. Si no, crear uno igual que en aquella verificación (`php artisan tinker`, `User::register()` + `activate()` + `authorization:assign-role` con `super_admin`).

- [ ] **Step 3: Verificar el flujo completo en modo claro**

- Cargar `/courses` (o navegar desde `/organizations` usando el nuevo enlace "Cursos" de la topbar): confirmar que la lista se ve bien, sin cursos aún.
- Crear un curso con todos los campos (incluida una modalidad y duración) → confirmar que redirige a la lista con mensaje de éxito, y que aparece con la modalidad legible ("Presencial"/"Virtual"/"Híbrida") y el badge de estado "Borrador" (amarillo/warning).
- Publicar el curso → confirmar mensaje de éxito y que el badge cambia a "Publicado" (verde/success), y que el botón "Publicar" ya no aparece para ese curso.
- Intentar publicar ese mismo curso de nuevo (recargar y usar el botón, si sigue visible en algún curso en borrador) → confirmar que aparece el mensaje de error correspondiente en vez de una página de error.
- Archivar un curso → confirmar el diálogo de confirmación nativo del navegador, y tras aceptar, el badge cambia a "Archivado" (rojo/danger) y ya no quedan botones de acción para ese curso.
- Navegar usando los enlaces "Organizaciones"/"Cursos" de la topbar entre ambas secciones.

- [ ] **Step 4: Verificar en modo oscuro**

- Repetir una parte del flujo (listar, ver los 3 badges de estado, crear) con el tema oscuro activado, confirmando contraste legible.

- [ ] **Step 5: Si algo se ve mal**

Corregir el archivo Blade correspondiente, volver al Step 3.

- [ ] **Step 6: Confirmar `composer quality` una vez más como cierre**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-cursos-web composer quality
```
Expected: PASS.

No hay commit en esta tarea salvo que el Step 5 haya requerido una corrección.

---

## Fuera de alcance (no hacer en este plan)

- Página de detalle de un curso individual (no existe `GET /api/v1/academic/courses/{id}`).
- Edición de un curso ya creado.
- Resaltado de sección activa en la navegación, o cualquier navegación más allá de dos enlaces de texto.
- Historial de estados o versionado curricular (ENG-029).
