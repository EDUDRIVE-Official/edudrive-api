# Diseño — Panel web de Organizaciones (login + listar + crear)

## 1. Información del documento

| Campo | Valor |
|---|---|
| Fecha | 2026-08-01 |
| Proyecto | EDUDRIVE2026 |
| Componentes afectados | `edudrive-api` |
| Tipo | Diseño de nueva funcionalidad (brainstorming) |
| Estado | Aprobado por el usuario, pendiente de plan de implementación |

## 2. Contexto

El diseño `2026-07-31-design-system-web-componentes-design.md` dejó explícitamente fuera de alcance "integrar estos componentes en una pantalla real de la aplicación (eso vendría después, cuando exista una necesidad concreta — por ejemplo, un panel de organizaciones)". Ese momento llegó: el backend de `Organization` (crear/listar organizaciones, vía `CommandBus`/`QueryBus`) y de `Authorization` (catálogo de permisos `organizations.manage`/`organizations.view`, middleware `permission`) ya existen y están probados desde la historia de alcance reducido de Autorización/Organizaciones (`2026-07-29-...-design.md`, completada el 2026-07-31).

Al explorar el código se confirmaron varios puntos relevantes:

- El guard `web` (sesión, `config/auth.php`) ya está configurado con `UserModel` como provider, pero no se usa en ningún lado todavía.
- `bootstrap/app.php` ya tiene `redirectGuestsTo` apuntando a `/login` para peticiones que no sean `api/*` ni esperen JSON — alguien dejó esto preparado de antemano.
- El login existente (`Modules\Identity\...\LoginController` + `LoginUserUseCase`) es exclusivamente para la API: valida credenciales y estado del usuario, y siempre emite un token de acceso Sanctum.
- El middleware `permission` (`EnsurePermission`) **siempre** devuelve una respuesta JSON (`ApiErrorResponse::make`), sin negociar contenido según el tipo de petición — a diferencia del manejo de excepciones en `bootstrap/app.php`, que sí distingue `api/*`/`expectsJson()` de web. Esto no se había notado porque el middleware solo se usaba en rutas `api/*`.
- El design system (`resources/views/components/ui/*`) tiene 5 componentes atómicos (`button`, `input`, `card`, `badge`, `table`) y un layout de referencia (`layouts/design-system.blade.php` con script de tema inline + Alpine + `@vite`), pero ningún componente `select`/modal, y ningún layout de aplicación autenticada (topbar, usuario, logout).

## 3. Objetivo

Construir la primera pantalla real de la aplicación detrás de un login funcional: un panel web mínimo donde un usuario autenticado con los permisos adecuados puede ver el listado de organizaciones y crear una organización nueva, usando el backend y los componentes de UI ya existentes.

## 4. Alcance

### 4.1 Autenticación web (sesión)

- Nuevo `LoginWebController` (junto al `LoginController` de API, en `Modules\Identity\Presentation\Http\Controllers`) reusa `LoginUserUseCase::execute()` — mismas reglas de dominio que la API (credenciales válidas + `status()->canAuthenticate()`) — y luego busca el `UserModel` por el `userId` de la respuesta y ejecuta `Auth::login($user)` sobre el guard `web`.
  - Trade-off aceptado conscientemente: `LoginUserUseCase` siempre emite un token Sanctum, incluso para login web. Cada login web deja una fila sin uso en `personal_access_tokens`. No se modifica el caso de uso para hacer esto opcional — se acepta el trade-off ahora; se revisita si se vuelve un problema real.
  - Credenciales inválidas o usuario que no puede autenticarse (`InvalidCredentials`/`UserCannotAuthenticate`): se vuelve al formulario de login con un único mensaje de error genérico (sin distinguir el motivo).
- Nuevo `LogoutWebController`: `Auth::logout()` + invalida la sesión, redirige a `/login`.
- Nuevas rutas en `modules/Identity/Presentation/Routes/web.php` (mismo patrón `loadRoutesFrom` que el `api.php` existente, cargado desde `IdentityServiceProvider::boot()`):
  - `GET /login`, `POST /login` (solo invitados).
  - `POST /logout` (requiere sesión).

### 4.2 Corrección en `EnsurePermission` (Authorization)

`EnsurePermission::handle()` se corrige para negociar contenido igual que `bootstrap/app.php` ya hace con las excepciones: si la petición es `api/*` o espera JSON, mantiene el comportamiento actual (`ApiErrorResponse::make`); en cualquier otro caso (petición web), usa `abort(401, ...)`/`abort(403, ...)` para que Laravel renderice su página de error HTML estándar. Es un cambio acotado, directamente necesario para poder usar este middleware en rutas web sin romper la experiencia en el navegador.

### 4.3 Panel de Organizaciones (web)

Nuevo `OrganizationWebController` (junto al `OrganizationController` de API), reusando el mismo `CommandBus`/`QueryBus`, `CreateOrganizationCommand` y `CreateOrganizationRequest` que ya existen — solo cambia la capa de presentación.

Nuevas rutas en `modules/Organization/Presentation/Routes/web.php` (mismo patrón de carga que su `api.php`):

| Ruta | Middleware | Acción |
|---|---|---|
| `GET /organizations` | `auth`, `permission:organizations.view` | Lista organizaciones (nombre, tipo, cantidad de sedes) |
| `GET /organizations/create` | `auth`, `permission:organizations.manage` | Formulario de creación |
| `POST /organizations` | `auth`, `permission:organizations.manage` | Crea y redirige a `organizations.index` con mensaje flash |

El botón "Nueva organización" en la lista solo se muestra si el usuario autenticado tiene `organizations.manage` (se resuelve llamando a `PermissionChecker::userHasPermission()` desde el controlador y pasando un booleano `canManage` a la vista — no se introduce un helper de Blade/Gate nuevo).

### 4.4 Design system: layout de aplicación y vistas

- Nuevo `resources/views/components/layouts/app.blade.php`: mismo patrón que `layouts/design-system.blade.php` (script de tema inline, `@vite`, toggle de tema), con una topbar: nombre de la app a la izquierda; a la derecha, si hay sesión, el nombre/email del usuario y un botón "Cerrar sesión" (formulario `POST /logout`); el toggle de tema se mantiene siempre visible. Si no hay sesión (pantalla de login), el bloque de usuario/logout no se renderiza.
- `resources/views/auth/login.blade.php`: `<x-layouts.app>` con una `<x-ui.card>` centrada conteniendo `<x-ui.input>` (email, password) y `<x-ui.button>` "Ingresar". Errores de validación de campo vía `$errors->first(...)` como prop `error` de cada input; error de credenciales inválidas como texto por encima del formulario.
- `resources/views/organizations/index.blade.php`: `<x-ui.table>` con columnas Nombre / Tipo / Sedes; mensaje flash de éxito (`session('status')`) sobre la tabla; botón "Nueva organización" condicionado a `canManage`.
- `resources/views/organizations/create.blade.php`: `<x-ui.card>` con `<x-ui.input>` (nombre), un `<select>` HTML nativo para `type` (los 5 valores de `OrganizationType`) y `<x-ui.button>` "Crear". No se construye un componente `select` de design system ni modal — fuera de alcance.

## 5. Manejo de errores

- Invitado en cualquier ruta protegida → redirige a `/login` (ya cubierto por `redirectGuestsTo`, sin cambios).
- Autenticado sin el permiso requerido → página de error 403 estándar de Laravel (gracias a 4.2), no un JSON crudo.
- Errores de validación en formularios (login, crear organización) → vuelven a la misma vista con `$errors` pobladas, renderizados por `<x-ui.input :error="...">`.

## 6. Pruebas

- `LoginWebTest` (Feature): login correcto establece sesión y redirige a `/organizations`; credenciales inválidas vuelve al formulario con error; usuario inactivo (`canAuthenticate()` false) rechazado.
- `OrganizationWebTest` (Feature): invitado a `/organizations` redirige a `/login`; usuario autenticado sin `organizations.view` recibe 403 HTML; usuario con permiso ve la lista con los datos correctos; `GET`/`POST /organizations/create` sin `organizations.manage` → 403; con permiso, crea la organización y redirige con mensaje flash.
- Ajuste a las pruebas existentes de `EnsurePermission`: agregar el caso de que una petición web sin permiso reciba una respuesta HTML/abort 403, no JSON.
- Se reusan los helpers existentes `actingAsAuthenticatedUser()` / `actingAsSuperAdminUser()` de `tests/Pest.php`, pero autenticando con `$this->actingAs($model)` (guard de sesión) en vez de `Sanctum::actingAs()`. No se agregan helpers nuevos.
- `composer format` y `composer quality` (Pint, Larastan/PHPStan, Pest) en verde, como en toda historia de este proyecto.
- Verificación visual real en navegador (claro y oscuro): login, lista vacía, lista con datos, formulario de creación y su resultado — siguiendo la política ya establecida para cambios de UI.

## 7. Fuera de alcance

- Editar o eliminar organizaciones; gestión de sedes desde la web; asignación de membresías o roles desde la web.
- Registro de usuarios por web — el primer administrador se sigue creando por CLI (`authorization:assign-role`); la brecha de auto-servicio de registro ya está documentada como pendiente en `ENG-000` sección 25.
- "Recordarme", recuperación de contraseña, verificación de correo electrónico.
- Cualquier componente nuevo de design system (select estilizado, modal, dropdown) — se usa `<select>` nativo de HTML.
- Sidebar o navegación multi-sección — la topbar es lo único que se construye ahora; se amplía cuando exista una segunda sección real.
- Hacer opcional la emisión de token Sanctum en `LoginUserUseCase` — se acepta el trade-off descrito en 4.1.

## 8. Siguiente paso

Este diseño pasa a un plan de implementación detallado (vía la skill `writing-plans`), desglosado en tareas TDD concretas con sus pruebas y comandos de validación (`composer format`, `composer quality`, verificación visual en navegador).
