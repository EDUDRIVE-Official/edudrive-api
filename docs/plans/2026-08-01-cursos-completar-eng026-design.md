# Diseño — Completar ENG-026 (Cursos): campos, publicar/archivar, permisos

## 1. Información del documento

| Campo | Valor |
|---|---|
| Fecha | 2026-08-01 |
| Proyecto | EDUDRIVE2026 |
| Componentes afectados | `edudrive-api` |
| Tipo | Diseño de nueva funcionalidad (brainstorming) |
| Estado | Aprobado por el usuario, pendiente de plan de implementación |

## 2. Contexto

`docs/roadmap/ENG-000-roadmap-tecnico-backend.md` (sección 11, Fase 5 — Catálogo educativo) registra que el agregado `Course` se adelantó parcialmente bajo IMP-020/IMP-021, cubriendo solo una parte de **ENG-026 — Cursos**: datos generales (código, título, descripción) y creación/listado vía `CommandBus`/`QueryBus`. El roadmap lista el alcance completo de ENG-026 como: datos generales, objetivos, requisitos, duración, modalidad, estado de publicación y versionado. Tras cerrar la historia de Autorización/Organizaciones (IMP-022) y construir el panel web de Organizaciones (IMP-023), la historia técnica activa vuelve a Academic — esta historia completa el resto de ENG-026.

Al explorar el código actual se confirmó:

- `Course` (agregado) ya tiene los métodos de dominio `publish()`/`archive()`/`rename()`/`changeDescription()` implementados y probados a nivel unitario, pero **ningún caso de uso ni endpoint los usa** — solo existen `CreateCourseHandler`/`ListCoursesHandler`.
- `GET`/`POST /api/v1/academic/courses` **no exigen autenticación en absoluto** — a diferencia de `Organization`, no tienen `auth:sanctum` ni ningún middleware de permiso.
- Las excepciones de dominio de Academic (`CourseAlreadyPublished`, `CourseAlreadyArchived`, `ArchivedCourseCannotBeModified`, `CourseCodeAlreadyExists`) extienden `\DomainException` de PHP (la clase base del lenguaje), no `Modules\Foundation\Domain\Exceptions\DomainException` (la clase del proyecto, con `errorCode()`/`statusCode()`, que el manejador global en `bootstrap/app.php` sabe convertir a una respuesta HTTP `ApiErrorResponse`). Esto es un **bug real preexistente**: hoy, crear un curso con un código duplicado (`CourseCodeAlreadyExists`) no es capturado por ningún manejador específico y cae en el catch-all de `Throwable`, devolviendo un 500 genérico en vez de un 409 — y no hay ningún test que cubra ese caso para haberlo detectado antes.
- El roadmap también lista "Versionado" dentro de ENG-026, pero existe una historia futura dedicada exactamente a eso: **ENG-029 — Publicación y versionado curricular** (borradores, revisión, aprobación, versiones, historial). Construir un sistema de versionado real aquí duplicaría/adelantaría ENG-029 sin necesidad.

## 3. Objetivo

Completar ENG-026 (Cursos) dejando diferido explícitamente solo el versionado curricular real (ENG-029) y la edición general de un curso ya creado: agregar los campos de dominio que faltan (objetivos, requisitos, modalidad, duración), exponer `publish`/`archive` como endpoints reales, proteger todos los endpoints de `Academic` con el mismo modelo de permisos que ya usa `Organization`, y corregir el bug de manejo de excepciones detectado.

## 4. Alcance

### 4.1 Dominio: campos nuevos

- Migración nueva sobre `academic_courses` (columnas nullable, no rompe datos existentes): `objectives` (text), `prerequisites` (text), `modality` (string), `duration_hours` (integer).
- Nuevo enum `Modules\Academic\Domain\Enums\CourseModality`: casos `InPerson` (`'in_person'`), `Virtual` (`'virtual'`), `Hybrid` (`'hybrid'`) — mismo patrón que `OrganizationType`/`CourseStatus` (enum respaldado por string, sin lógica adicional salvo lo que ya existe en `CourseStatus`).
- `Course::create()` acepta los 4 campos nuevos como parámetros opcionales (`?string $objectives = null`, `?string $prerequisites = null`, `?CourseModality $modality = null`, `?int $durationHours = null`), con getters y normalización de texto igual que `description` (trim, `''` → `null`). `Course::restore()` los recibe igual que los demás campos existentes (obligatorios en esa firma, como el resto).
- **Explícitamente NO se agrega** ningún método de dominio para modificar estos campos en un curso ya creado (no hay `changeObjectives()`, etc.) — coincide con que hoy tampoco existe un endpoint de edición general, y evita construir mutadores sin un caso de uso que los llame.
- `CourseListItemResponse`/`CreateCourseResponse` exponen los 4 campos nuevos (más `modality` ya como string del enum, no el objeto).

### 4.2 Corrección de manejo de excepciones (bug fix)

- `CourseAlreadyPublished`, `CourseAlreadyArchived`, `ArchivedCourseCannotBeModified` (en `Modules\Academic\Domain\Exceptions`) pasan de extender `\DomainException` a extender `Modules\Foundation\Domain\Exceptions\DomainException`, con `statusCode: 422` y un `errorCode` propio (ej. `COURSE_ALREADY_PUBLISHED`).
- `CourseCodeAlreadyExists` (en `Modules\Academic\Application\Exceptions`) igual, con `statusCode: 409` (conflicto — el recurso ya existe con esa clave) y `errorCode: 'COURSE_CODE_ALREADY_EXISTS'`.
- Nueva excepción `Modules\Academic\Application\Exceptions\CourseNotFound`, mismo patrón que `Modules\Organization\Application\Exceptions\OrganizationNotFound`: `statusCode: 404`, `errorCode: 'COURSE_NOT_FOUND'`.
- Ningún otro comportamiento de estas excepciones cambia — solo la clase base, que activa el manejador genérico de `DomainException` ya registrado en `bootstrap/app.php` (sin tocar ese archivo).

### 4.3 Casos de uso y endpoints nuevos: publicar y archivar

- `PublishCourseCommand(string $courseId)` → `PublishCourseHandler`: busca el curso por `CourseId` vía `CourseRepository::findById()` (lanza `CourseNotFound` si no existe), llama `$course->publish(new DateTimeImmutable())` (lanza `CourseAlreadyPublished`/`ArchivedCourseCannotBeModified` si corresponde — ya implementado en el dominio), guarda vía `CourseRepository::save()`. Responde con los datos del curso actualizado (reusa `CourseListItemResponse`/un `PublishCourseResponse` análogo a `CreateCourseResponse`).
- `ArchiveCourseCommand(string $courseId)` → `ArchiveCourseHandler`: mismo patrón, llama `$course->archive(new DateTimeImmutable())` (lanza `CourseAlreadyArchived`).
- Nuevas rutas en `modules/Academic/Presentation/Routes/api.php`:
  - `POST /api/v1/academic/courses/{courseId}/publish` → `CourseController::publish()`.
  - `POST /api/v1/academic/courses/{courseId}/archive` → `CourseController::archive()`.
- Ambas devuelven 200 con los datos del curso actualizado en caso de éxito.

### 4.4 Permisos y autenticación

- Nuevos casos en `Modules\Authorization\Domain\Enums\Permission`: `ManageCourses` (`'courses.manage'`), `ViewCourses` (`'courses.view'`).
- `Modules\Authorization\Domain\Services\RolePermissions::permissionsFor()`: `SuperAdmin` obtiene ambos; `InstitutionalAdmin`, `Teacher`, `Student` obtienen solo `ViewCourses` — mismo patrón exacto que `Organization`.
- Todas las rutas de cursos de `Academic` (existentes y nuevas) pasan a requerir `auth:sanctum` (`GET /status` queda fuera de este cambio y sigue pública, mismo criterio que `Organization`):
  - `GET /courses` → agrega `permission:courses.view`.
  - `POST /courses`, `POST /courses/{id}/publish`, `POST /courses/{id}/archive` → agregan `permission:courses.manage`.
- **Cambio de comportamiento explícito**: hoy estos endpoints no piden autenticación; después de esta historia sí. No hay consumidores en producción todavía (proyecto pre-lanzamiento), así que no hay compatibilidad hacia atrás que romper, pero se deja anotado por si algún script/colección de pruebas manual asumía acceso anónimo.

## 5. Manejo de errores (resumen)

| Situación | Antes | Después |
|---|---|---|
| Código de curso duplicado al crear | 500 genérico (bug) | 409, `COURSE_CODE_ALREADY_EXISTS` |
| Publicar un curso ya publicado | (no existía endpoint) | 422, `COURSE_ALREADY_PUBLISHED` |
| Archivar un curso ya archivado | (no existía endpoint) | 422, `COURSE_ALREADY_ARCHIVED` |
| Publicar/archivar un curso archivado (regla `ensureIsNotArchived`) | (no existía endpoint) | 422, `ARCHIVED_COURSE_CANNOT_BE_MODIFIED` |
| Publicar/archivar un `{id}` inexistente | (no existía endpoint) | 404, `COURSE_NOT_FOUND` |
| Crear/publicar/archivar sin permiso `courses.manage` | (no existía protección) | 403 (vía middleware `permission`, ya corregido para negociar contenido) |
| Listar/crear sin sesión autenticada | (no existía protección) | 401 |

## 6. Pruebas

- Actualizar `CreateCourseTest`: agregar autenticación (`actingAsSuperAdminUser()`), un caso de rechazo por falta de permiso (usuario con rol `Student`, que solo tiene `courses.view`), y un caso nuevo de código duplicado → 409 (cubre el bug corregido).
- Actualizar `ListCoursesTest`: agregar autenticación; un caso de rechazo por falta de sesión.
- Nuevo `PublishCourseTest`: éxito (curso en borrador → publicado, `published_at` seteado), curso ya publicado → 422, curso archivado → 422, curso inexistente → 404, sin permiso → 403.
- Nuevo `ArchiveCourseTest`: éxito desde borrador y desde publicado, curso ya archivado → 422, curso inexistente → 404, sin permiso → 403.
- Pruebas unitarias del agregado `Course` (`CourseTest`) para los 4 campos nuevos: normalización de `objectives`/`prerequisites` (igual que `description`), `modality` opcional, `durationHours` opcional.
- `composer format`/`composer quality` (Pint, Larastan, Pest) en verde.

## 7. Fuera de alcance

- Sistema de versionado/historial curricular real (ENG-029) — se difiere como su propia historia futura.
- Endpoint de edición general de un curso ya existente (título, descripción, objetivos, requisitos, modalidad, duración) — no se agregan mutadores de dominio nuevos ni endpoint `PATCH`.
- ENG-024 (catálogo de competencias), ENG-025 (programas educativos), ENG-027 (módulos y unidades), ENG-028 (lecciones) — historias separadas, no tocadas aquí.
- Contexto organizacional en los permisos de cursos (`courses.manage`/`courses.view` son globales por usuario, igual que hoy son los de Organización — ENG-014 sigue pendiente en general, no se resuelve aquí para Academic tampoco).

## 8. Siguiente paso

Este diseño pasa a un plan de implementación detallado (vía la skill `writing-plans`), desglosado en tareas TDD concretas.
