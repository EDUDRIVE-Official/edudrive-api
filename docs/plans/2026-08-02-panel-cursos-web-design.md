# Diseño — Panel web de Cursos (listar, crear, publicar, archivar)

## 1. Información del documento

| Campo | Valor |
|---|---|
| Fecha | 2026-08-02 |
| Proyecto | EDUDRIVE2026 |
| Componentes afectados | `edudrive-api` |
| Tipo | Diseño de nueva funcionalidad (brainstorming) |
| Estado | Aprobado por el usuario, pendiente de plan de implementación |

## 2. Contexto

ENG-026 (Cursos) se cerró el 2026-08-02 (IMP-024): el agregado `Course` tiene campos completos (código, título, descripción, objetivos, requisitos, modalidad, duración, estado) y cuatro endpoints JSON reales (`GET/POST /api/v1/academic/courses`, `POST .../publish`, `POST .../archive`), protegidos por los permisos `courses.view`/`courses.manage`. Todo ese trabajo es puramente de API — no existe ninguna pantalla web para Cursos, a diferencia de Organizaciones, que sí tiene un panel web (login + listar + crear) construido el 2026-08-01.

Al revisar el código existente antes de diseñar esto se confirmó:

- El layout compartido `<x-layouts.app>` (topbar con usuario/logout/tema) no tiene ningún enlace de navegación entre secciones — se dejó así deliberadamente en el diseño de Organizaciones ("se amplía cuando exista una segunda sección real"). Con Cursos como segunda sección real, este es el momento de agregarlos.
- `CourseModality` (enum `in_person`/`virtual`/`hybrid`) y `CourseStatus` (`draft`/`published`/`archived`) no tienen ningún método de etiqueta legible en español — solo `OrganizationType` lo tiene (`label()`, agregado en la revisión final del panel de Organizaciones tras detectarse que un valor de enum se mostraba crudo en una vista). Para no repetir ese mismo defecto, este diseño agrega `label()` a ambos enums desde el principio.
- El manejador genérico de `DomainException` en `bootstrap/app.php` solo produce una respuesta JSON cuando la petición es `api/*` o espera JSON (`$request->is('api/*') || $request->expectsJson()`); para cualquier otra petición devuelve `null`, y la excepción cae sin manejar hacia el catch-all de `Throwable` — un error 500 genérico. Esto afectaría directamente a `publish`/`archive` desde la web (ej. publicar un curso ya publicado), si no se maneja explícitamente en el controlador web, igual que ya hace `LoginWebController` con `InvalidCredentials`/`UserCannotAuthenticate`.
- No existe ningún endpoint `GET /api/v1/academic/courses/{id}` — no hay forma de leer el detalle de un curso individual. Esto limita el alcance de la vista de listado (no puede haber una página de detalle) y del formulario de creación (no puede haber edición).

## 3. Objetivo

Construir un panel web para Cursos con el mismo nivel de funcionalidad que ya tiene Organizaciones (listar, crear) más las dos acciones de estado que Cursos sí tiene y Organizaciones no (publicar, archivar), reusando el backend y los componentes de UI ya existentes, sin construir una página de detalle ni edición (no hay endpoint de lectura individual).

## 4. Alcance

### 4.1 Arquitectura y rutas

- Nuevo `CourseWebController` (junto al `CourseController` de API, en `Modules\Academic\Presentation\Http\Controllers`), reusando exactamente el mismo `CommandBus`/`QueryBus` y los mismos `CreateCourseCommand`/`CreateCourseRequest`/`PublishCourseCommand`/`ArchiveCourseCommand` ya existentes — solo cambia la capa de presentación (Blade en vez de JSON), igual patrón que `OrganizationWebController`.
- Nuevas rutas en `modules/Academic/Presentation/Routes/web.php` (cargado desde `AcademicServiceProvider::boot()`, mismo patrón `loadRoutesFrom` que el `api.php` existente):

| Ruta | Middleware | Acción |
|---|---|---|
| `GET /courses` | `auth`, `permission:courses.view` | Listar |
| `GET /courses/create` | `auth`, `permission:courses.manage` | Formulario de creación |
| `POST /courses` | `auth`, `permission:courses.manage` | Crear |
| `POST /courses/{courseId}/publish` | `auth`, `permission:courses.manage` | Publicar |
| `POST /courses/{courseId}/archive` | `auth`, `permission:courses.manage` | Archivar |

- `resources/views/components/layouts/app.blade.php` gana dos enlaces de texto simples ("Organizaciones", "Cursos") junto al nombre de la app en la topbar, visibles solo con `@auth` — sin sidebar, sin dropdown, sin resaltado de sección activa (fuera de alcance por ahora).

### 4.2 Etiquetas legibles para los enums

- `Modules\Academic\Domain\Enums\CourseModality::label(): string` — `InPerson` → `'Presencial'`, `Virtual` → `'Virtual'`, `Hybrid` → `'Híbrida'`. Método puro, aditivo, sin cambio de comportamiento (mismo criterio que `OrganizationType::label()`).
- `Modules\Academic\Domain\Enums\CourseStatus::label(): string` — `Draft` → `'Borrador'`, `Published` → `'Publicado'`, `Archived` → `'Archivado'`.

### 4.3 Vistas

- `resources/views/courses/index.blade.php`: `<x-ui.table>` con columnas Código, Título, Modalidad, Duración, Estado (con `<x-ui.badge>`: `warning` para borrador, `success` para publicado, `danger` para archivado). Por cada fila, si `canManage` es verdadero: botón "Publicar" (formulario `POST`, solo visible si el curso está en borrador) y botón "Archivar" (formulario `POST` con `onsubmit="return confirm(...)"`, visible si no está ya archivado). Botón "Nuevo curso" arriba de la tabla (enlace `<a>` con las clases del botón primario, igual criterio que "Nueva organización" — no se anida `<x-ui.button>` dentro de `<a>`), visible solo si `canManage`. Mensaje flash de éxito o error sobre la tabla.
- `resources/views/courses/create.blade.php`: `<x-ui.input>` para código y título (obligatorios); `<textarea>` nativo estilizado a mano (mismo criterio que el `<select>` nativo ya usado en Organizaciones — no se crea un componente de design system nuevo) para descripción, objetivos y requisitos; `<select>` nativo con las etiquetas de `CourseModality::label()` para modalidad; `<x-ui.input type="number">` para duración en horas — todos estos opcionales, igual que ya exige `CreateCourseRequest`.

### 4.4 Manejo de errores en publicar/archivar

`CourseWebController::publish()`/`archive()` envuelven el `$commandBus->dispatch(...)` en un `try/catch (Modules\Foundation\Domain\Exceptions\DomainException $exception)`. Esa única clase base cubre `CourseAlreadyPublished`, `CourseAlreadyArchived`, `ArchivedCourseCannotBeModified` y `CourseNotFound` (las 4 ya extienden esa clase desde IMP-024, con mensajes limpios en español). En caso de excepción: `redirect()->route('courses.index')->with('error', $exception->getMessage())`. En caso de éxito: mismo redirect con `with('status', '...')`.

## 5. Manejo de errores (resumen)

| Situación | Comportamiento |
|---|---|
| Invitado en cualquier ruta de cursos | Redirige a `/login` (ya funciona, sin cambios) |
| Autenticado sin el permiso requerido | Página 403 (layout con logout, ya corregido en el panel de Organizaciones) |
| Validación al crear (código/título faltante, modalidad inválida, duración no numérica) | Vuelve al formulario con `$errors`, igual que Organizaciones |
| Publicar/archivar con transición de estado inválida, o curso inexistente | Redirige a la lista con mensaje flash de error (4.4) — **no** un 500 |

## 6. Pruebas

- `CoursesIndexWebTest`: invitado → redirige a login; sin `courses.view` → 403; con `courses.view` ve la lista con etiquetas legibles (no valores crudos del enum) y sin botones de acción ni "Nuevo curso"; con `courses.manage` ve además el botón "Nuevo curso" y los botones publicar/archivar según el estado de cada curso.
- `CoursesCreateWebTest`: sin `courses.manage` → 403 en `GET`/`POST`; validación de campos obligatorios y modalidad inválida; creación exitosa con todos los campos → redirige a la lista con flash y el curso aparece con sus datos correctos.
- `CoursesPublishArchiveWebTest`: publicar un curso en borrador → redirige con flash de éxito y el curso pasa a publicado; archivar un curso en borrador o publicado → redirige con flash de éxito; publicar un curso ya publicado y archivar uno ya archivado → redirige con flash de **error** (mensaje de la excepción, no 500); sin `courses.manage` → 403 en ambas acciones.
- `composer quality` (Pint, Larastan, Pest) en verde.
- Verificación visual manual en navegador (claro y oscuro): listar con distintos estados, crear, publicar, archivar (incluyendo el diálogo de confirmación nativo), navegación entre Organizaciones y Cursos vía los enlaces nuevos de la topbar.

## 7. Fuera de alcance

- Página de detalle de un curso individual (no existe `GET /api/v1/academic/courses/{id}`).
- Edición de un curso ya creado (título, descripción, objetivos, etc.).
- Resaltado de sección activa en la navegación de la topbar, o cualquier navegación más allá de dos enlaces de texto.
- Historial de estados o versionado curricular (ENG-029).

## 8. Siguiente paso

Este diseño pasa a un plan de implementación detallado (vía la skill `writing-plans`), desglosado en tareas TDD concretas.
