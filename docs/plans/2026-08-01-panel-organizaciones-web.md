# Panel web de Organizaciones (login + listar + crear) — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir la primera pantalla real de la aplicación detrás de un login funcional: un panel web donde un usuario autenticado con los permisos adecuados ve el listado de organizaciones y puede crear una organización nueva, reusando el backend (`Organization`, `Authorization`, `Identity`) y los componentes de UI ya existentes.

**Architecture:** Nuevos controladores "web" (Blade, no JSON) conviven junto a los controladores de API existentes en cada módulo (`Modules\{Identity,Organization}\Presentation\Http\Controllers`), reusando exactamente los mismos casos de uso/comandos/queries de dominio (`LoginUserUseCase`, `CreateOrganizationCommand` vía `CommandBus`, `ListOrganizationsQuery` vía `QueryBus`). La sesión web usa el guard `web` (Eloquent + `UserModel`), ya configurado pero sin usar. El middleware `permission` (`EnsurePermission`) se corrige para negociar contenido (JSON solo para `api/*`/`expectsJson()`, HTML en cualquier otro caso) antes de reusarlo en rutas web. Un nuevo layout Blade (`layouts.app`) con topbar de usuario/logout envuelve todas las pantallas nuevas.

**Tech Stack:** Laravel 12, Blade components, guard de sesión `web`, Pest.

## Global Constraints

- PHP 8.4 / Laravel 12, sin cambios de versión ni de dependencias.
- `declare(strict_types=1);` en todo archivo PHP nuevo.
- Todos los comandos de PHP/Composer se ejecutan vía Docker (ver "Convenciones" abajo), no con PHP local del host.
- `composer quality` (Pint check + Larastan/PHPStan + Pest) en verde después de cada tarea, antes de comitear.
- No se modifica ningún módulo de dominio existente (`Domain`/`Application` de `Organization`, `Identity`, `Authorization`) salvo el fix puntual de `EnsurePermission` en la Tarea 1 — todo lo demás es capa `Presentation`/vistas nueva.
- Se reusan exactamente `CreateOrganizationCommand`, `CreateOrganizationRequest`, `ListOrganizationsQuery`, `OrganizationListItemResponse`, `LoginUserUseCase`, `LoginUserCommand`, `PermissionChecker` ya existentes — no se duplica lógica de dominio.
- Diseño aprobado: `docs/plans/2026-08-01-panel-organizaciones-web-design.md`.

---

## Convenciones usadas en este plan (leer una vez)

- Todos los comandos PHP/Composer se ejecutan vía Docker:
  ```bash
  MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web <comando>
  ```
  Si la imagen `edudrive-worktree-organizations-web` no existe todavía en el worktree (primera vez), construirla una sola vez antes de la Tarea 1:
  ```bash
  MSYS_NO_PATHCONV=1 docker build -t edudrive-worktree-organizations-web -f docker/php/Dockerfile .
  ```
- Este plan **no toca** `resources/css/app.css` ni `resources/js/app.js` (no se agregan tokens ni dependencias JS nuevas), así que **no hace falta correr `npm install`/`npm run build`** en ninguna tarea — todas las vistas nuevas son Blade puro sobre lo que ya existe.
- Las pruebas usan SQLite en memoria (`phpunit.xml`, `RefreshDatabase`) — no requieren PostgreSQL/Redis reales corriendo.
- Los helpers de test ya existentes en `tests/Pest.php` (`actingAsAuthenticatedUser()`, `actingAsSuperAdminUser()`) devuelven un `UserModel` y ya hacen `Sanctum::actingAs()` internamente (guard de API). Para las pruebas de rutas **web** de este plan, adicionalmente se llama `$this->actingAs($model)` (sin segundo argumento, usa el guard `web` por defecto) sobre el mismo modelo devuelto — no se crean helpers globales nuevos.
- Después de **cada** tarea: correr `composer quality` y confirmar verde antes de comitear.

---

### Task 1: Corregir `EnsurePermission` para negociar contenido web/API

**Files:**
- Modify: `modules/Authorization/Presentation/Http/Middleware/EnsurePermission.php`
- Test: `modules/Authorization/Tests/Feature/EnsurePermissionWebNegotiationTest.php`

**Interfaces:**
- Consumes: nada nuevo (usa `PermissionChecker`, `Permission`, `ApiErrorResponse` ya existentes).
- Produces: `EnsurePermission::handle()` ahora responde HTML (`abort()`) para peticiones que no sean `api/*` ni esperen JSON, en vez de JSON siempre. Ningún consumidor existente cambia de comportamiento (todas las rutas actuales están bajo `api/*`).

- [ ] **Step 1: Escribir el test que debe fallar**

Crear `modules/Authorization/Tests/Feature/EnsurePermissionWebNegotiationTest.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

beforeEach(function (): void {
    Route::middleware(['web'])
        ->get('/test-permission-web', fn (): string => 'contenido protegido')
        ->middleware('permission:organizations.manage');
});

it('responde con una página web (no JSON) cuando no hay sesión en una ruta que no es de la API', function (): void {
    /** @var TestCase $this */
    $response = $this->get('/test-permission-web');

    $response->assertUnauthorized();
    expect($response->headers->get('content-type'))->not->toContain('application/json');
});

it('responde con una página web (no JSON) cuando falta el permiso en una ruta que no es de la API', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user);

    $response = $this->get('/test-permission-web');

    $response->assertForbidden();
    expect($response->headers->get('content-type'))->not->toContain('application/json');
});
```

- [ ] **Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Authorization/Tests/Feature/EnsurePermissionWebNegotiationTest.php
```
Expected: FAIL (ambas respuestas son JSON hoy, `content-type` sí contiene `application/json`).

- [ ] **Step 3: Corregir `EnsurePermission`**

Reemplazar el contenido completo de `modules/Authorization/Presentation/Http/Middleware/EnsurePermission.php`:
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
use ValueError;

/**
 * Must run after an authentication middleware (e.g. `auth:sanctum` or
 * `auth`) that populates `$request->user()` for the correct guard.
 */
final readonly class EnsurePermission
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->respondWithError(
                $request,
                message: 'Debe autenticarse para acceder a este recurso.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        try {
            $requiredPermission = Permission::from($permission);
        } catch (ValueError) {
            return $this->respondWithError(
                $request,
                message: 'La configuración de permisos de esta ruta no es válida.',
                status: 500,
                code: 'INVALID_PERMISSION_CONFIGURATION',
            );
        }

        if (! $this->checker->userHasPermission(
            (string) $user->getAuthIdentifier(),
            $requiredPermission,
        )) {
            return $this->respondWithError(
                $request,
                message: 'No tiene permisos para realizar esta acción.',
                status: 403,
                code: 'FORBIDDEN',
            );
        }

        return $next($request);
    }

    private function respondWithError(
        Request $request,
        string $message,
        int $status,
        string $code,
    ): Response {
        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiErrorResponse::make(
                message: $message,
                status: $status,
                code: $code,
            );
        }

        abort($status, $message);
    }
}
```

- [ ] **Step 4: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Authorization/Tests/Feature/EnsurePermissionWebNegotiationTest.php
```
Expected: PASS.

- [ ] **Step 5: Correr `composer quality` completo (confirmar que las rutas API existentes siguen devolviendo JSON)**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS (incluye `CreateOrganizationTest`, que ya cubre el caso de permiso denegado vía API y debe seguir devolviendo JSON).

- [ ] **Step 6: Commit**

```bash
git add modules/Authorization/Presentation/Http/Middleware/EnsurePermission.php modules/Authorization/Tests/Feature/EnsurePermissionWebNegotiationTest.php
git commit -m "fix(authorization): negotiate content type in EnsurePermission for web routes"
```

---

### Task 2: Layout base de aplicación autenticada (`<x-layouts.app>`)

**Files:**
- Create: `resources/views/components/layouts/app.blade.php`

Sin test automatizado propio (es Blade/CSS puro, mismo criterio que `layouts/design-system.blade.php`) — se verifica al usarse en las Tareas 3 a 5, y visualmente en la Tarea 6.

**Interfaces:**
- Consumes: `resources/css/app.css`/`resources/js/app.js` ya existentes (tokens, Alpine); `<x-ui.button>` ya existente.
- Produces: componente `<x-layouts.app :title="...">{{ slot }}</x-layouts.app>`. Si hay sesión autenticada (`@auth`), renderiza nombre/correo del usuario y un formulario `POST` a la ruta nombrada `logout` (no existe todavía — se agrega en la Tarea 3; como está dentro de `@auth`, no se evalúa mientras no haya usuarios autenticados usando este layout, así que no rompe nada antes de la Tarea 3).

- [ ] **Step 1: Crear el layout**

Crear `resources/views/components/layouts/app.blade.php`:
```blade
@props(['title' => 'EDUDRIVE'])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <script>
        (function () {
            var stored = localStorage.getItem('edudrive-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans text-text">
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <span class="font-heading text-lg font-bold text-text">EDUDRIVE</span>
            <div class="flex items-center gap-4">
                @auth
                    <span class="font-sans text-sm text-text-secondary">
                        {{ auth()->user()->name }} ({{ auth()->user()->email }})
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">Cerrar sesión</x-ui.button>
                    </form>
                @endauth
                <button
                    type="button"
                    x-data
                    @click="
                        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                        document.documentElement.setAttribute('data-theme', next);
                        localStorage.setItem('edudrive-theme', next);
                    "
                    class="rounded-sm border border-border px-3 py-2 text-sm text-text hover:bg-background focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Cambiar tema
                </button>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8">
        {{ $slot }}
    </main>
</body>
</html>
```

- [ ] **Step 2: Correr `composer quality`**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/layouts/app.blade.php
git commit -m "feat(web): add authenticated app layout with topbar"
```

---

### Task 3: Autenticación web (login y logout, sesión)

**Files:**
- Create: `modules/Identity/Presentation/Http/Controllers/LoginWebController.php`
- Create: `modules/Identity/Presentation/Http/Controllers/LogoutWebController.php`
- Create: `modules/Identity/Presentation/Http/Requests/LoginWebRequest.php`
- Create: `modules/Identity/routes/web.php`
- Create: `resources/views/auth/login.blade.php`
- Modify: `modules/Identity/Providers/IdentityServiceProvider.php`
- Modify: `bootstrap/app.php`
- Test: `modules/Identity/Tests/Feature/LoginWebTest.php`

**Interfaces:**
- Consumes: `LoginUserUseCase::execute(LoginUserCommand): LoginUserResponse` (campos `userId`, `name`, `email`, `status`, `accessToken`, `tokenType`), excepciones `InvalidCredentials`/`UserCannotAuthenticate`, `UserModel` (Eloquent, tabla `users`), `<x-layouts.app>` de la Tarea 2.
- Produces: rutas nombradas `login` (GET), `login.attempt` (POST), `logout` (POST); vista `auth.login`. El login exitoso redirige a la ruta literal `/organizations` (string, no nombre de ruta — esa ruta se crea recién en la Tarea 4, así que no se referencia por nombre todavía).

- [ ] **Step 1: Escribir el test que debe fallar**

Crear `modules/Identity/Tests/Feature/LoginWebTest.php`:
```php
<?php

declare(strict_types=1);

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use Tests\TestCase;

it('muestra el formulario de login a un invitado', function (): void {
    /** @var TestCase $this */
    $this->get('/login')->assertOk()->assertSeeText('Ingresar');
});

it('inicia sesión con credenciales válidas y redirige al panel de organizaciones', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertRedirect('/organizations');
    $this->assertAuthenticatedAs(UserModel::query()->findOrFail($user->id()));
});

it('rechaza credenciales inválidas y vuelve al formulario con un error', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-incorrecta',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
    $response->assertSessionHas('loginError');
});

it('rechaza el login de un usuario que todavía no puede autenticarse', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Pendiente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $repository->save($user);

    $response = $this->post('/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ]);

    $response->assertRedirect();
    $this->assertGuest();
});

it('cierra la sesión y redirige al login', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);
    $hasher = app(PasswordHasher::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario Web',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());
    $this->actingAs($model);

    $response = $this->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
```

- [ ] **Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Identity/Tests/Feature/LoginWebTest.php
```
Expected: FAIL (404, las rutas `/login` y `/logout` no existen todavía).

- [ ] **Step 3: Crear `LoginWebRequest`**

Crear `modules/Identity/Presentation/Http/Requests/LoginWebRequest.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginWebRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
```

- [ ] **Step 4: Crear `LoginWebController`**

Crear `modules/Identity/Presentation/Http/Controllers/LoginWebController.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Identity\Application\Commands\LoginUserCommand;
use Modules\Identity\Application\UseCases\LoginUserUseCase;
use Modules\Identity\Domain\Exceptions\InvalidCredentials;
use Modules\Identity\Domain\Exceptions\UserCannotAuthenticate;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Identity\Presentation\Http\Requests\LoginWebRequest;

final class LoginWebController extends Controller
{
    public function __construct(
        private readonly LoginUserUseCase $useCase,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginWebRequest $request): RedirectResponse
    {
        try {
            $response = $this->useCase->execute(
                new LoginUserCommand(
                    email: (string) $request->string('email'),
                    password: (string) $request->string('password'),
                    tokenName: 'web',
                ),
            );
        } catch (InvalidCredentials|UserCannotAuthenticate) {
            return back()
                ->withInput($request->only('email'))
                ->with('loginError', 'El correo o la contraseña no son válidos.');
        }

        $user = UserModel::query()->findOrFail($response->userId);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect('/organizations');
    }
}
```

- [ ] **Step 5: Crear `LogoutWebController`**

Crear `modules/Identity/Presentation/Http/Controllers/LogoutWebController.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutWebController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

- [ ] **Step 6: Crear la vista de login**

Crear `resources/views/auth/login.blade.php`:
```blade
<x-layouts.app title="EDUDRIVE — Ingresar">
    <div class="mx-auto flex max-w-sm flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Ingresar</h1>

        @if (session('loginError'))
            <p class="font-sans text-sm text-danger-text">{{ session('loginError') }}</p>
        @endif

        <x-ui.card>
            <form method="POST" action="{{ route('login.attempt') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="email"
                    type="email"
                    label="Correo electrónico"
                    value="{{ old('email') }}"
                    :error="$errors->first('email')"
                />
                <x-ui.input
                    name="password"
                    type="password"
                    label="Contraseña"
                    :error="$errors->first('password')"
                />
                <x-ui.button type="submit" variant="primary">Ingresar</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
```

- [ ] **Step 7: Crear las rutas web de Identity**

Crear `modules/Identity/routes/web.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\LoginWebController;
use Modules\Identity\Presentation\Http\Controllers\LogoutWebController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginWebController::class, 'create'])->name('login');
        Route::post('/login', [LoginWebController::class, 'store'])->name('login.attempt');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutWebController::class)->name('logout');
    });
});
```

- [ ] **Step 8: Registrar las rutas en `IdentityServiceProvider`**

Modificar `modules/Identity/Providers/IdentityServiceProvider.php`: en el método `boot()`, después de `$this->loadRoutesFrom(__DIR__.'/../routes/api.php');`, agregar:
```php
        $this->loadRoutesFrom(
            __DIR__.'/../routes/web.php',
        );
```

- [ ] **Step 9: Redirigir a un usuario ya autenticado que visite `/login` al panel, no al bienvenido**

Modificar `bootstrap/app.php`: dentro del closure `withMiddleware`, después del bloque `$middleware->redirectGuestsTo(...)`, agregar:
```php
        $middleware->redirectUsersTo(
            static fn (Request $request): string => '/organizations',
        );
```

- [ ] **Step 10: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Identity/Tests/Feature/LoginWebTest.php
```
Expected: PASS.

Nota: esta tarea deja el flujo de login apuntando a `/organizations`, que todavía no existe como ruta hasta la Tarea 4 — la prueba de "credenciales válidas" verifica el `Location` del redirect (`assertRedirect('/organizations')`), no que esa página cargue; eso se cubre a partir de la Tarea 4.

- [ ] **Step 11: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS.

- [ ] **Step 12: Commit**

```bash
git add modules/Identity/Presentation/Http/Controllers/LoginWebController.php modules/Identity/Presentation/Http/Controllers/LogoutWebController.php modules/Identity/Presentation/Http/Requests/LoginWebRequest.php modules/Identity/routes/web.php modules/Identity/Providers/IdentityServiceProvider.php resources/views/auth/login.blade.php bootstrap/app.php modules/Identity/Tests/Feature/LoginWebTest.php
git commit -m "feat(identity): add web login and logout (session-based)"
```

---

### Task 4: Panel de Organizaciones — listar

**Files:**
- Create: `modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php`
- Create: `modules/Organization/Presentation/Routes/web.php`
- Create: `resources/views/organizations/index.blade.php`
- Modify: `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`
- Test: `modules/Organization/Tests/Feature/OrganizationsIndexWebTest.php`

**Interfaces:**
- Consumes: `QueryBus::ask(ListOrganizationsQuery): array` (elementos `OrganizationListItemResponse`, método `toArray(): array{id: string, name: string, type: string, campuses: list<array{id: string, name: string}>}`), `PermissionChecker::userHasPermission(string, Permission): bool`, `Permission::ViewOrganizations`/`Permission::ManageOrganizations`, `<x-layouts.app>` (Tarea 2), ruta nombrada `login` (Tarea 3, para el redirect de invitados).
- Produces: `OrganizationWebController::index()`; ruta nombrada `organizations.index` en `GET /organizations`, protegida por `auth` + `permission:organizations.view`; vista `organizations.index` que recibe `organizations` (array de arrays) y `canManage` (bool).

- [ ] **Step 1: Escribir el test que debe fallar**

Crear `modules/Organization/Tests/Feature/OrganizationsIndexWebTest.php`:
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
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;

use Tests\TestCase;

it('redirige a un invitado que intenta ver la lista de organizaciones', function (): void {
    /** @var TestCase $this */
    $this->get('/organizations')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin ninguna asignación de rol', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user);

    $this->get('/organizations')->assertForbidden();
});

it('muestra la lista a un usuario con permiso de vista, sin el botón de crear', function (): void {
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

    $organizations = app(OrganizationRepository::class);
    $organizations->save(Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Centro Educativo EDUDRIVE'),
        type: OrganizationType::EducationalCenter,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()));

    $response = $this->get('/organizations');

    $response->assertOk();
    $response->assertSeeText('Centro Educativo EDUDRIVE');
    $response->assertDontSeeText('Nueva organización');
});

it('muestra el botón de crear a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user);

    $response = $this->get('/organizations');

    $response->assertOk();
    $response->assertSeeText('Nueva organización');
});
```

- [ ] **Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Organization/Tests/Feature/OrganizationsIndexWebTest.php
```
Expected: FAIL (404, la ruta `/organizations` no existe todavía).

- [ ] **Step 3: Crear el controlador**

Crear `modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php`:
```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;

final class OrganizationWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(
            new ListOrganizationsQuery,
        );

        assert(is_array($result));

        /** @var list<OrganizationListItemResponse> $result */
        $organizations = array_map(
            static fn (OrganizationListItemResponse $organization): array => $organization->toArray(),
            $result,
        );

        return view('organizations.index', [
            'organizations' => $organizations,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageOrganizations,
            ),
        ]);
    }
}
```

- [ ] **Step 4: Crear la vista**

Crear `resources/views/organizations/index.blade.php`:
```blade
<x-layouts.app title="EDUDRIVE — Organizaciones">
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">Organizaciones</h1>
            @if ($canManage)
                <a
                    href="{{ route('organizations.create') }}"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-sm bg-primary px-4 font-sans font-medium text-white transition hover:bg-secondary focus-visible:outline-none focus-visible:shadow-focus"
                >
                    Nueva organización
                </a>
            @endif
        </div>

        @if (session('status'))
            <p class="font-sans text-sm text-success">{{ session('status') }}</p>
        @endif

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-4 py-2">Nombre</th>
                    <th scope="col" class="px-4 py-2">Tipo</th>
                    <th scope="col" class="px-4 py-2">Sedes</th>
                </tr>
            </x-slot:head>
            @forelse ($organizations as $organization)
                <tr>
                    <td class="px-4 py-2">{{ $organization['name'] }}</td>
                    <td class="px-4 py-2">{{ $organization['type'] }}</td>
                    <td class="px-4 py-2">{{ count($organization['campuses']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-2 text-text-secondary" colspan="3">Todavía no hay organizaciones registradas.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
```

Nota: el enlace "Nueva organización" no usa `<x-ui.button>` para evitar anidar un `<button>` dentro de un `<a>` (marcado inválido); reusa manualmente las clases de la variante `primary` del botón.

- [ ] **Step 5: Crear las rutas web de Organization**

Crear `modules/Organization/Presentation/Routes/web.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:organizations.view')->group(function (): void {
        Route::get('/organizations', [OrganizationWebController::class, 'index'])
            ->name('organizations.index');
    });
});
```

- [ ] **Step 6: Registrar las rutas en `OrganizationServiceProvider`**

Modificar `modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php`: en `boot()`, después de `$this->loadRoutesFrom(dirname(__DIR__, 2).'/Presentation/Routes/api.php');`, agregar:
```php
        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/web.php',
        );
```

- [ ] **Step 7: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Organization/Tests/Feature/OrganizationsIndexWebTest.php
```
Expected: PASS.

- [ ] **Step 8: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php modules/Organization/Presentation/Routes/web.php modules/Organization/Infrastructure/Providers/OrganizationServiceProvider.php resources/views/organizations/index.blade.php modules/Organization/Tests/Feature/OrganizationsIndexWebTest.php
git commit -m "feat(organization): add web organizations list"
```

---

### Task 5: Panel de Organizaciones — crear

**Files:**
- Modify: `modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php`
- Modify: `modules/Organization/Presentation/Routes/web.php`
- Create: `resources/views/organizations/create.blade.php`
- Test: `modules/Organization/Tests/Feature/OrganizationsCreateWebTest.php`

**Interfaces:**
- Consumes: `CreateOrganizationRequest` (reglas ya existentes: `name` requerido máx. 180, `type` requerido y debe ser un `OrganizationType` válido), `CommandBus::dispatch(CreateOrganizationCommand)`, `OrganizationType::cases()`, ruta nombrada `organizations.index` (Tarea 4, ya registrada).
- Produces: `OrganizationWebController::create()`/`store()`; rutas nombradas `organizations.create` (GET) y `organizations.store` (POST), ambas protegidas por `auth` + `permission:organizations.manage`; vista `organizations.create`.

- [ ] **Step 1: Escribir el test que debe fallar**

Crear `modules/Organization/Tests/Feature/OrganizationsCreateWebTest.php`:
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

    $this->actingAs(UserModel::query()->findOrFail($user->id()));

    $this->get('/organizations/create')->assertForbidden();
    $this->post('/organizations', ['name' => 'X', 'type' => 'company'])->assertForbidden();
});

it('muestra el formulario de creación a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user);

    $this->get('/organizations/create')
        ->assertOk()
        ->assertSeeText('Nueva organización');
});

it('crea una organización y redirige a la lista con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user);

    $response = $this->post('/organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);

    $response->assertRedirect(route('organizations.index'));
    $response->assertSessionHas('status');

    assertDatabaseHas('organizations', [
        'name' => 'Escuela de Manejo EDUDRIVE',
        'type' => 'driving_school',
    ]);
});

it('vuelve al formulario con errores cuando faltan datos obligatorios', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user);

    $response = $this->post('/organizations', []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name', 'type']);
});
```

- [ ] **Step 2: Correr el test y confirmar que falla**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Organization/Tests/Feature/OrganizationsCreateWebTest.php
```
Expected: FAIL (404, `/organizations/create` no existe todavía).

- [ ] **Step 3: Agregar `create()`/`store()` al controlador**

Modificar `modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php` (reemplazar el contenido completo):
```php
<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;

final class OrganizationWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(
            new ListOrganizationsQuery,
        );

        assert(is_array($result));

        /** @var list<OrganizationListItemResponse> $result */
        $organizations = array_map(
            static fn (OrganizationListItemResponse $organization): array => $organization->toArray(),
            $result,
        );

        return view('organizations.index', [
            'organizations' => $organizations,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageOrganizations,
            ),
        ]);
    }

    public function create(): View
    {
        return view('organizations.create', [
            'types' => OrganizationType::cases(),
        ]);
    }

    public function store(
        CreateOrganizationRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $validated = $request->validated();

        $commandBus->dispatch(
            new CreateOrganizationCommand(
                name: (string) $validated['name'],
                type: (string) $validated['type'],
            ),
        );

        return redirect()
            ->route('organizations.index')
            ->with('status', 'Organización creada correctamente.');
    }
}
```

- [ ] **Step 4: Crear la vista**

Crear `resources/views/organizations/create.blade.php`:
```blade
@php
    $typeLabels = [
        'educational_center' => 'Centro educativo',
        'driving_school' => 'Escuela de manejo',
        'company' => 'Empresa',
        'public_institution' => 'Institución pública',
        'other' => 'Otro',
    ];
@endphp
<x-layouts.app title="EDUDRIVE — Nueva organización">
    <div class="mx-auto flex max-w-sm flex-col gap-6">
        <h1 class="font-heading text-2xl font-bold">Nueva organización</h1>

        <x-ui.card>
            <form method="POST" action="{{ route('organizations.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.input
                    name="name"
                    label="Nombre"
                    value="{{ old('name') }}"
                    :error="$errors->first('name')"
                />

                <div class="flex flex-col gap-1">
                    <label for="type" class="font-sans text-sm font-medium text-text">Tipo de organización</label>
                    <select
                        id="type"
                        name="type"
                        class="min-h-[44px] rounded-sm border border-border bg-surface px-3 font-sans text-base text-text focus-visible:outline-none focus-visible:shadow-focus"
                    >
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                                {{ $typeLabels[$type->value] ?? $type->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="font-sans text-sm text-danger-text">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.button type="submit" variant="primary">Crear</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
```

- [ ] **Step 5: Agregar las rutas de creación**

Modificar `modules/Organization/Presentation/Routes/web.php` (contenido completo):
```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:organizations.view')->group(function (): void {
        Route::get('/organizations', [OrganizationWebController::class, 'index'])
            ->name('organizations.index');
    });

    Route::middleware('permission:organizations.manage')->group(function (): void {
        Route::get('/organizations/create', [OrganizationWebController::class, 'create'])
            ->name('organizations.create');

        Route::post('/organizations', [OrganizationWebController::class, 'store'])
            ->name('organizations.store');
    });
});
```

- [ ] **Step 6: Correr el test y confirmar que pasa**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan test modules/Organization/Tests/Feature/OrganizationsCreateWebTest.php
```
Expected: PASS.

- [ ] **Step 7: Correr `composer quality` completo**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add modules/Organization/Presentation/Http/Controllers/OrganizationWebController.php modules/Organization/Presentation/Routes/web.php resources/views/organizations/create.blade.php modules/Organization/Tests/Feature/OrganizationsCreateWebTest.php
git commit -m "feat(organization): add web organization creation form"
```

---

### Task 6: Verificación visual final (navegador)

**Files:** ninguno (solo verificación; posibles commits de corrección si algo se ve mal)

- [ ] **Step 1: Levantar el servidor de desarrollo**

Usar las herramientas de preview/browser del entorno apuntando al servidor de desarrollo Laravel (`php artisan serve` dentro del contenedor, o el contenedor `app` si ya corre con nginx) sirviendo los assets ya compilados (no hace falta `npm run dev`/`build`: esta feature no tocó CSS/JS).

- [ ] **Step 2: Preparar un usuario de prueba**

Dentro del contenedor de la app (con la base de datos real, no SQLite de test):
```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web php artisan tinker --execute="
    \$repo = app(Modules\Identity\Domain\Repositories\UserRepository::class);
    \$hasher = app(Modules\Identity\Application\Services\PasswordHasher::class);
    \$user = Modules\Identity\Domain\Entities\User::register(
        id: (string) Illuminate\Support\Str::uuid(),
        name: 'Admin de Prueba',
        email: Modules\Identity\Domain\ValueObjects\Email::fromString('admin-visual@edudrive.cr'),
        passwordHash: \$hasher->hash('clave-visual-123'),
    );
    \$user->activate(new DateTimeImmutable);
    \$repo->save(\$user);
    app(Modules\Authorization\Domain\Repositories\RoleAssignmentRepository::class)->save(
        Modules\Authorization\Domain\Entities\RoleAssignment::assign(
            id: (string) Illuminate\Support\Str::uuid(),
            userId: \$user->id(),
            role: Modules\Authorization\Domain\Enums\Role::SuperAdmin,
            organizationId: null,
        ),
    );
    echo 'listo: admin-visual@edudrive.cr / clave-visual-123';
"
```

- [ ] **Step 3: Verificar el flujo completo en modo claro**

- Cargar `/login`: confirmar que el formulario se ve bien, sin sesión iniciada no aparece el bloque de usuario/logout en la topbar.
- Enviar credenciales incorrectas: confirmar que vuelve al formulario con el mensaje de error en rojo.
- Enviar `admin-visual@edudrive.cr` / `clave-visual-123`: confirmar que redirige a `/organizations`, la topbar ahora muestra el nombre/correo y el botón "Cerrar sesión", y aparece el botón "Nueva organización".
- Hacer clic en "Nueva organización": confirmar el formulario, el `<select>` con las etiquetas legibles de tipo.
- Crear una organización: confirmar que redirige a la lista, aparece el mensaje de éxito en verde y la fila con el nombre/tipo/0 sedes.
- Hacer clic en "Cerrar sesión": confirmar que vuelve a `/login` y que visitar `/organizations` directamente ahora redirige de nuevo a `/login`.

- [ ] **Step 4: Verificar en modo oscuro**

- Repetir el flujo de login → lista → crear con el tema oscuro activado (botón "Cambiar tema"), confirmando contraste legible en topbar, card, tabla, inputs y mensajes de error/éxito.

- [ ] **Step 5: Si algo se ve mal**

Corregir el archivo Blade correspondiente, volver al Step 3.

- [ ] **Step 6: Confirmar `composer quality` una vez más como cierre**

```bash
MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html edudrive-worktree-organizations-web composer quality
```
Expected: PASS.

No hay commit en esta tarea salvo que el Step 5 haya requerido una corrección — en ese caso, commitear con un mensaje descriptivo (ej. `fix(web): correct organizations table contrast in dark mode`).

---

## Fuera de alcance (no hacer en este plan)

- Editar/eliminar organizaciones, gestión de sedes o membresías desde la web.
- Registro de usuarios por web, recuperación de contraseña, verificación de correo, "recordarme".
- Componentes nuevos de design system (select estilizado, modal, dropdown).
- Sidebar o navegación multi-sección.
- Hacer opcional la emisión de token Sanctum en `LoginUserUseCase` durante el login web (trade-off aceptado en el diseño).
