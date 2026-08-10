# ENG-LOG

---

# 2026-07-27 — Hito ENG-008
## Autenticación y Activación de Usuarios

### Estado

✅ COMPLETADO

---

## Objetivo

Implementar la primera versión completa del módulo Identity, incluyendo registro, autenticación, activación de usuarios y emisión de tokens mediante Laravel Sanctum.

---

## Funcionalidades implementadas

### Registro de usuarios

- Registro mediante API REST.
- Validación de datos.
- Persistencia en PostgreSQL.
- Hash seguro de contraseñas.
- Estado inicial `pending`.

---

### Login

Se implementó el flujo completo de autenticación.

Incluye:

- Login por correo electrónico.
- Validación de contraseña.
- Generación de Access Token mediante Laravel Sanctum.
- Respuesta estandarizada.

---

### Estados de usuario

Se implementó el modelo de estados:

- pending
- active
- inactive
- locked

Regla de negocio:

Únicamente usuarios con estado **active** pueden autenticarse.

---

### Activación de usuarios

Se implementó el flujo de activación.

Proceso:

1. Buscar usuario.
2. Activar cuenta.
3. Persistir cambios.
4. Permitir autenticación.

---

### Laravel Sanctum

Integración completa.

Incluye:

- Personal Access Tokens.
- Emisión de tokens.
- Modelo User compatible.
- Configuración de Sanctum.
- Migraciones.

---

### Excepciones de dominio

Se incorporaron:

- InvalidCredentials
- UserCannotAuthenticate
- UserNotFound

Todas integradas con el manejador global de excepciones del proyecto.

---

### Endpoints disponibles

POST /api/v1/auth/register

POST /api/v1/auth/login

POST /api/v1/auth/users/{userId}/activate

---

## Pruebas realizadas

### Registro

Resultado:

201 Created

---

### Login usuario pendiente

Resultado esperado:

403 Forbidden

Código:

USER_CANNOT_AUTHENTICATE

---

### Activación

Resultado:

Usuario activado correctamente.

---

### Login usuario activo

Resultado:

200 OK

Generación correcta de Access Token.

---

## Calidad

Validaciones ejecutadas satisfactoriamente:

- Laravel Pint
- PHPStan Nivel 8
- Pest
- Pruebas manuales mediante Postman

---

## Commit

8bbaf59

feat(identity): implementar autenticación y activación de usuarios

---

## Estado del proyecto

El módulo Identity cuenta con una primera versión completamente funcional.

La autenticación ya puede ser utilizada por los módulos futuros del ecosistema EDUDRIVE.

---

## Próximo hito

ENG-009

Cerrar completamente la capa de autenticación:

- Endpoint /me
- Logout
- Revocación de tokens
- Middleware auth:sanctum
- Protección de rutas privadas
- Pruebas automáticas

---

# 2026-07-27 — Hito ENG-009
## Cierre de la Capa de Autenticación (Fase 1)

### Estado

✅ COMPLETADO

---

## Objetivo

Completar la primera fase de autenticación del backend EDUDRIVE, incorporando identificación del usuario autenticado, cierre de sesión mediante Sanctum y refactor de la integración de autenticación para alinearla con la arquitectura modular del proyecto.

---

# Funcionalidades implementadas

## Usuario autenticado

Se implementó el endpoint:

GET /api/v1/auth/me

Permite obtener la información del usuario autenticado utilizando el Bearer Token emitido por Laravel Sanctum.

Información retornada:

- Id
- Nombre
- Correo electrónico
- Estado

---

## Cierre de sesión

Se implementó:

POST /api/v1/auth/logout

Funcionalidad:

- Revoca únicamente el token utilizado en la petición actual.
- Mantiene activas las demás sesiones del usuario.
- Devuelve respuesta estandarizada.

---

## Middleware de autenticación

Se incorporó protección mediante:

auth:sanctum

Los endpoints privados ahora requieren autenticación válida.

---

## Refactor de integración con Sanctum

Se realizó una mejora arquitectónica importante.

### Situación anterior

Existían dos modelos Eloquent independientes:

App\Models\User

Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel

Lo anterior generaba duplicidad de responsabilidades.

---

### Situación actual

El proveedor de autenticación utiliza directamente:

Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel

El modelo:

App\Models\User

queda únicamente como adaptador de compatibilidad para el ecosistema Laravel.

Toda la configuración del usuario reside ahora dentro del módulo Identity.

---

## Nuevos servicios

Se incorporaron:

AccessTokenRevoker

SanctumAccessTokenRevoker

LogoutUserUseCase

LogoutController

GetAuthenticatedUserUseCase

AuthenticatedUserResponse

MeController

---

## Endpoints disponibles

POST /api/v1/auth/register

POST /api/v1/auth/login

POST /api/v1/auth/users/{userId}/activate

GET /api/v1/auth/me

POST /api/v1/auth/logout

---

## Pruebas funcionales realizadas

### Registro

Resultado:

201 Created

---

### Login

Resultado:

200 OK

Access Token generado correctamente.

---

### Consulta de usuario autenticado

GET /api/v1/auth/me

Resultado:

200 OK

Datos del usuario obtenidos correctamente.

---

### Logout

POST /api/v1/auth/logout

Resultado:

200 OK

Token revocado correctamente.

---

### Validación posterior

Se reutilizó el mismo Bearer Token.

Resultado:

401 Unauthorized

Confirmando la revocación exitosa del token.

---

## Calidad

Validaciones ejecutadas satisfactoriamente:

- Laravel Pint
- PHPStan Nivel 8
- Pruebas manuales mediante Postman

---

## Arquitectura

Se mantiene el cumplimiento de:

- Arquitectura Modular
- DDD
- Casos de Uso
- Repositorios
- DTO
- Separación entre Application, Domain, Infrastructure y Presentation
- Integración desacoplada con Laravel Sanctum

---

## Estado actual del módulo Identity

El módulo Identity ya ofrece:

- Registro
- Activación
- Login
- Usuario autenticado
- Logout
- Tokens Sanctum
- Estados de usuario
- Protección de rutas
- Manejo de excepciones de dominio

La primera fase de autenticación queda oficialmente completada.

---

## Próximo hito

ENG-010

Gestión avanzada de sesiones:

- Logout global (logout-all)
- Revocación de todos los tokens
- Refresh de sesión (si aplica)
- Recuperación de contraseña
- Cambio de contraseña
- Base para MFA

---

# 2026-07-27 — Hito ENG-010.1

## Logout global de sesiones

### Estado

✅ COMPLETADO

### Endpoint

POST /api/v1/auth/logout-all

### Funcionalidad

Permite revocar todos los tokens activos del usuario autenticado, cerrando la sesión en todos los dispositivos.

### Casos de uso

- Pérdida o robo de un dispositivo.
- Cambio de contraseña.
- Sospecha de acceso no autorizado.
- Cierre global de sesiones.

### Componentes implementados

- AccessTokenRevoker::revokeAllForUser()
- SanctumAccessTokenRevoker
- LogoutAllUsersUseCase
- LogoutAllController
- Ruta protegida con auth:sanctum

### Validaciones realizadas

- Login múltiple.
- Generación de múltiples tokens.
- Revocación global.
- Verificación de invalidez de todos los tokens.

### Resultado

El endpoint revoca correctamente todas las sesiones del usuario y devuelve respuesta JSON estandarizada.

---

# 2026-07-28 — Hito ENG-010.2

## Gestión de sesiones activas

### Estado

✅ COMPLETADO

### Endpoint

GET /api/v1/auth/sessions

### Objetivo

Permitir al usuario consultar todas las sesiones activas asociadas a su cuenta.

### Componentes implementados

- SessionData
- SessionRepository
- SanctumSessionRepository
- GetUserSessionsUseCase
- SessionsController

### Características

- Obtiene todas las sesiones activas.
- Identifica la sesión actual.
- Expone:
  - id
  - nombre
  - current
  - created_at
  - last_used_at

### Arquitectura

La capa Application depende únicamente de SessionRepository.

La infraestructura implementa dicho contrato mediante SanctumSessionRepository, evitando acoplamiento con Laravel Sanctum.

### Resultado

El endpoint devuelve correctamente todas las sesiones del usuario autenticado mediante una respuesta JSON estandarizada.


---

# ENG-010 — Cierre del Módulo Identity y Auditoría

**Fecha:** 2026-07-29

## Objetivo

Completar el módulo de autenticación (Identity) e integrar el módulo de auditoría (Audit) para registrar todos los eventos críticos relacionados con la autenticación de usuarios.

## Actividades realizadas

### Infraestructura

- Configuración definitiva de PostgreSQL como base de datos principal.
- Corrección de la configuración Docker para utilizar PostgreSQL en lugar de SQLite.
- Verificación de la conectividad entre Laravel y PostgreSQL.

### Sanctum

- Adaptación de Laravel Sanctum para trabajar con UUID.
- Actualización de la tabla `personal_access_tokens` utilizando `uuidMorphs()`.
- Validación del correcto funcionamiento de autenticación mediante Bearer Tokens.

### Identity

Se completaron los siguientes casos de uso:

- Registro de usuarios.
- Activación de cuentas.
- Inicio de sesión.
- Cierre de sesión actual.
- Cierre de todas las sesiones.
- Consulta de usuario autenticado.
- Consulta de sesiones activas.

### Audit

Se implementó el nuevo módulo Audit siguiendo la arquitectura modular del proyecto.

#### Componentes implementados

- AuditEntry (DTO)
- AuditLogger (Contrato)
- AuditRepository (Contrato)
- EloquentAuditRepository
- DatabaseAuditLogger
- AuditLogModel
- AuditServiceProvider

#### Persistencia

Se creó la tabla:

- audit_logs

Campos principales:

- id
- user_id
- action
- entity
- entity_id
- ip
- user_agent
- metadata
- occurred_at
- timestamps

### Eventos auditados

Actualmente se registran automáticamente los siguientes eventos:

- auth.login
- auth.logout
- auth.logout_all

Cada registro almacena:

- Usuario
- Entidad afectada
- Identificador
- Metadata
- Fecha y hora del evento

## Correcciones realizadas

Durante este hito se solucionaron, entre otros, los siguientes problemas:

- Laravel utilizando SQLite por configuración incorrecta.
- UUID incompatibles con Sanctum.
- Registro del AuditServiceProvider.
- Error "Target AuditLogger is not instantiable".
- Respuesta JSON correcta para usuarios no autenticados.
- Auditoría integrada con Login, Logout y Logout All.

## Validaciones realizadas

Se verificó exitosamente:

- composer format
- composer quality

Pruebas funcionales mediante Postman:

- Registro
- Login
- Logout
- Logout All
- Me
- Sessions

Validación directa en PostgreSQL:

```sql
SELECT
    action,
    user_id,
    entity,
    entity_id,
    metadata,
    occurred_at
FROM audit_logs;
```

Confirmando la creación correcta de registros:

- auth.login
- auth.logout
- auth.logout_all

## Estado del proyecto

Módulos completados:

- Foundation
- Identity
- Audit

Estado de la infraestructura:

- Docker
- PostgreSQL
- Redis
- MinIO
- Mailpit

Estado general:

**Fase de Plataforma Base finalizada.**

La arquitectura técnica ya soporta el desarrollo de los módulos funcionales de EDUDRIVE.

## Próximo hito

ENG-020

**Implementación del módulo Academic**, que administrará la estructura académica oficial de EDUDRIVE:

- Cursos
- Módulos
- Lecciones
- Competencias
- Objetivos de aprendizaje
- Recursos
- Versionado curricular

## 2026-07-29 — IMP-020 (Bloque 1)

### Completado

- Reestructuración definitiva del repositorio Laravel.
- Integración del módulo Academic.
- Definición del estándar oficial de módulos (ENG-003).
- Endpoint `/api/v1/academic/status`.
- Aggregate Root `Course`.
- Value Objects:
  - CourseId
  - CourseCode
  - CourseTitle
- Enum `CourseStatus`.
- Excepciones de dominio.
- Migración `academic_courses`.
- Integración de pruebas modulares con Pest.
- Validación completa mediante:
  - composer test
  - composer analyse
  - composer quality

**Estado:** ✅ Finalizado.

## 2026-07-29 — IMP-020 (Bloque 3)

### Completado

- Implementación del contrato `CourseRepository`.
- Implementación de `CourseModel`.
- Implementación de `EloquentCourseRepository`.
- Registro del binding del repositorio en `AcademicServiceProvider`.
- Implementación de `CreateCourseCommand`.
- Implementación de `CreateCourseHandler`.
- Implementación de `CreateCourseRequest`.
- Implementación de `CourseController`.
- Creación del endpoint:
  - `POST /api/v1/academic/courses`
- Persistencia real de cursos en PostgreSQL.
- Normalización automática del código de curso.
- Validación de códigos duplicados desde la capa de aplicación.
- Reconstrucción del Aggregate `Course` desde Eloquent.
- Pruebas Feature para creación y validación.
- Pruebas de integración del repositorio.
- Integración completa de pruebas modulares en la suite global.

### Validaciones

- `composer test` ✅
- `composer analyse` ✅
- `composer quality` ✅

**Estado:** Finalizado.

## 2026-07-29 — IMP-021 (Bloque 1)

### Completado

- Implementación del contrato `Command`.
- Implementación del contrato `Query`.
- Implementación de `CommandBus`.
- Implementación de `QueryBus`.
- Implementación de `MessageHandlerRegistry`.
- Implementación de `MessageHandlerNotFound`.
- Implementación de `InMemoryMessageHandlerRegistry`.
- Implementación de `LaravelCommandBus`.
- Implementación de `LaravelQueryBus`.
- Registro de los buses en `FoundationServiceProvider`.
- Registro de handlers desde los módulos.
- Migración de `CreateCourseCommand` al `CommandBus`.
- Desacoplamiento de `CourseController` respecto a `CreateCourseHandler`.
- Pruebas unitarias del registro de handlers.
- Pruebas unitarias del `CommandBus`.
- Pruebas unitarias del `QueryBus`.
- Corrección de namespaces y cumplimiento PSR-4.
- Corrección automática de estilo con Pint.

### Validaciones

- `composer test` ✅
- `composer analyse` ✅
- `composer quality` ✅

**Estado:** Finalizado.

## 2026-07-31 — IMP-022 (Cierre de historia)

### Completado

- Módulo `Organization` completo:
  - Aggregate `Organization` y entidad `Campus`.
  - Value Objects `OrganizationId` y `OrganizationName`.
  - Enum `OrganizationType`.
  - Contrato `OrganizationRepository` y su implementación `EloquentOrganizationRepository`.
  - Migraciones `organizations` y `organization_campuses`.
  - Endpoints `POST /api/v1/organizations` (crear organización), `POST /api/v1/organizations/{id}/campuses` (agregar sede) y `GET /api/v1/organizations` (listar organizaciones con sus sedes).
  - Endpoint de estado `GET /api/v1/organizations/status`.
- Módulo `Authorization` completo:
  - Catálogo de roles y permisos (`Role`, `Permission`, `RolePermissions`) con los 4 roles mínimos viables: Superadministrador, Administrador institucional, Docente/Instructor y Estudiante.
  - Entidad `RoleAssignment` con soporte para asociación opcional a una organización (columna `organization_id` sin llave foránea entre módulos, siguiendo el precedente de `audit_logs.user_id`).
  - Contrato `RoleAssignmentRepository` y su implementación `EloquentRoleAssignmentRepository`.
  - Servicio `PermissionChecker` y su implementación `RoleAssignmentPermissionChecker`.
  - Middleware `permission` (`EnsurePermission`) registrado como alias, para proteger rutas por permiso.
  - Caso de uso `AssignRole` con endpoint protegido y comando de consola `authorization:assign-role` para el arranque inicial (bootstrap) del primer rol sin depender de un flujo HTTP.
  - Caso de uso `ListMyRoles` con endpoint para que un usuario autenticado consulte sus propias asignaciones de rol.
  - Endpoint de estado `GET /api/v1/authorization/status`.
- Integración entre ambos módulos: el permiso `organizations.manage` quedó exigido mediante el middleware `permission` en los endpoints de escritura de `Organization` (crear organización, agregar sede), dejando la lectura (listado) abierta a cualquier usuario autenticado.
- Suite de pruebas modular completa para ambos módulos (unitarias, de integración y de feature), incluyendo casos de rechazo por falta de autenticación, datos inválidos y falta de permiso.
- Actualización de `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` para cerrar formalmente esta historia técnica de alcance reducido y dejar registrado lo diferido explícitamente.

### Validaciones

- `composer test` ✅
- `composer analyse` ✅
- `composer quality` ✅

**Estado:** Finalizado.

## 2026-08-01 — IMP-023 (Corrección de EnsurePermission + panel web de Organizaciones)

### Completado

- Corrección de `Modules\Authorization\Presentation\Http\Middleware\EnsurePermission`: negocia contenido según el tipo de petición (`$request->is('api/*') || $request->expectsJson()`), igual que el resto de la aplicación (`bootstrap/app.php`). Antes devolvía siempre JSON (`ApiErrorResponse`), lo que no se había notado porque el middleware solo se usaba en rutas `api/*`. Sin este cambio, las rutas web nuevas habrían mostrado JSON crudo ante un 401/403.
- Primer panel web administrativo real, construido sobre los endpoints de `Organization`/`Authorization` ya completados en IMP-022, sin tocar ningún módulo `Domain`/`Application`:
  - Autenticación web con sesión (guard `web`): `LoginWebController`/`LogoutWebController` (`Modules\Identity\Presentation\Http\Controllers`), reusando `LoginUserUseCase` tal cual (mismas reglas de dominio que el login de la API). Rutas `GET/POST /login`, `POST /logout`.
  - Layout de aplicación autenticada `<x-layouts.app>` (topbar con usuario/logout/tema).
  - Panel de Organizaciones web: `OrganizationWebController` (`Modules\Organization\Presentation\Http\Controllers`), reusando `CommandBus`/`QueryBus`/`CreateOrganizationRequest` tal cual. Rutas `GET /organizations` (permiso `organizations.view`), `GET/POST /organizations/create` (permiso `organizations.manage`).
  - Vista de error `resources/views/errors/403.blade.php` envuelta en el mismo layout, para que un usuario autenticado sin permisos conserve el botón de cerrar sesión (evita quedar atrapado sin salida).
  - `OrganizationType::label()`: etiquetas legibles en español para el tipo de organización, usadas de forma consistente en listar y crear.
- Trade-off aceptado y documentado (`docs/plans/2026-08-01-panel-organizaciones-web-design.md`, sección 4.1): cada login web sigue emitiendo un token Sanctum sin uso (vía `LoginUserUseCase`), que no expira (`config/sanctum.php` `expiration => null`) ni se revoca al cerrar sesión web — aparece como "sesión activa" fantasma en `GET /api/v1/auth/sessions`. No se modificó el caso de uso para evitarlo.
- Suite de pruebas Feature nueva (login/logout web, negociación de contenido del middleware, listar/crear organizaciones vía web), con casos de rechazo por falta de sesión, falta de permiso y validación.
- Este trabajo es presentación web (Blade), no una historia técnica nueva del roadmap `ENG-000` (que cubre específicamente el backend) — no se le asigna ENG-XXX propio, mismo criterio ya aplicado a los componentes del design system. Detalle completo y diseño en `docs/plans/2026-08-01-panel-organizaciones-web-design.md` y `docs/plans/2026-08-01-panel-organizaciones-web.md`.

### Validaciones

- `composer test` ✅ (105 pruebas)
- `composer analyse` ✅
- `composer quality` ✅
- Verificación visual manual en navegador (modo claro y oscuro): login, error de credenciales, listar, crear, mensaje de éxito, cerrar sesión.

**Estado:** Finalizado.

## 2026-08-02 — IMP-024 (Cierre de ENG-026 — Cursos)

### Completado

- Ampliación del aggregate `Course`: nuevos campos `objectives`, `prerequisites`, `modality` (nuevo enum `CourseModality`: `in_person`/`virtual`/`hybrid`) y `duration_hours`, propagados a la migración de persistencia y al repositorio Eloquent.
- Ampliación del catálogo de permisos del módulo `Authorization`: `courses.manage` y `courses.view` (el catálogo pasa de 3 a 5 permisos). `SuperAdmin` recibe ambos; `InstitutionalAdmin`, `Teacher` y `Student` reciben únicamente `courses.view`.
- `GET`/`POST /api/v1/academic/courses`, hasta ahora sin autenticación, quedaron protegidos con `auth:sanctum` y el middleware `permission` (`courses.view`/`courses.manage` respectivamente); los 4 campos nuevos se conectaron a lo largo de toda la capa HTTP (request, comando, respuesta).
- Corrección de un defecto preexistente real detectado durante este trabajo: las excepciones de dominio de `Academic` (`CourseAlreadyPublished`, `CourseAlreadyArchived`, `ArchivedCourseCannotBeModified`, `CourseCodeAlreadyExists`) extendían el `\DomainException` nativo de PHP en lugar de `Modules\Foundation\Domain\Exceptions\DomainException`, por lo que el manejador de excepciones genérico de `bootstrap/app.php` no las reconocía y producían 500 crudos en vez del código HTTP correcto (por ejemplo, crear un curso con un código duplicado devolvía 500 en lugar de 409). Se corrigió cambiando la clase base de las 4 excepciones; no se tocó `bootstrap/app.php`.
- Nuevo endpoint `POST /api/v1/academic/courses/{id}/publish`, protegido por `courses.manage`.
- Nuevo endpoint `POST /api/v1/academic/courses/{id}/archive`, protegido por `courses.manage`.
- Ambos endpoints nuevos reutilizan `Course::publish()`/`Course::archive()`, métodos de dominio ya existentes y ya cubiertos por pruebas unitarias, que hasta ahora no tenían ningún caso de uso ni endpoint que los invocara. Se agregó una excepción `CourseNotFound` (404) compartida por ambos.
- Diferido explícitamente, para no duplicar/adelantar otras historias: el versionado curricular real (borradores, revisión, aprobación, historial de versiones — su propia historia futura, ENG-029), un endpoint de edición general de un curso ya existente, y permisos de cursos con alcance por organización.
- Actualización de `docs/roadmap/ENG-000-roadmap-tecnico-backend.md` para reflejar el cierre de ENG-026 (Parcial — ver sección 25) y mover la historia técnica activa a pendiente de decisión.

### Validaciones

- `composer test` ✅ (128 pruebas)
- `composer analyse` ✅
- `composer quality` ✅

**Estado:** Finalizado.

## 2026-08-03 — IMP-025 (Cierre de ENG-024 — Catálogo regional de competencias viales)

### Completado

- Nuevo agregado `Competency`, con `CompetencyId`, `CompetencyCode`, categorías controladas (`risk_management`, `road_rules`, `vehicle_control`, `vulnerable_road_users`, `eco_driving`) y niveles de dominio (`foundation`, `developing`, `proficient`, `advanced`).
- Jerarquía de entidades `Subcompetency` y `CompetencyIndicator`, con códigos normalizados, unicidad, orden explícito y reglas de modificación dentro del dominio.
- Persistencia PostgreSQL mediante las tablas `academic_competencies`, `academic_subcompetencies` y `academic_competency_indicators`, claves foráneas con eliminación en cascada y repositorio `EloquentCompetencyRepository` que reconstruye el agregado sin exponer modelos Eloquent.
- Casos de uso registrados en los buses existentes para crear competencias, agregar subcompetencias, agregar indicadores y consultar el catálogo jerárquico.
- Nuevos permisos `competencies.manage` y `competencies.view`: `SuperAdmin` recibe ambos; `InstitutionalAdmin`, `Teacher` y `Student` reciben solo consulta.
- API protegida con `auth:sanctum` y middleware de permisos:
  - `GET /api/v1/academic/competencies`
  - `POST /api/v1/academic/competencies`
  - `POST /api/v1/academic/competencies/{competencyId}/subcompetencies`
  - `POST /api/v1/academic/competencies/{competencyId}/subcompetencies/{subcompetencyCode}/indicators`
- Validación HTTP de códigos, campos obligatorios, categorías y niveles; conflicto 409 para código de competencia duplicado mediante `COMPETENCY_CODE_ALREADY_EXISTS`.
- Pruebas unitarias, de integración y Feature para reglas jerárquicas, persistencia, handlers, autorización, validación y consulta completa.
- Diferido explícitamente: perfiles normativos por país, asociaciones con cursos, evaluaciones o SIMUDRIVE, cálculo de dominio y versionado curricular (ENG-029).

### Validaciones

- `composer format` ✅ (263 archivos; 19 ajustes de estilo aplicados a ENG-024)
- `composer analyse` ✅ (194 archivos, sin errores)
- `composer quality` ✅ (143 pruebas, 409 aserciones)
- Setup del worktree completado con `npm ci` y `npm run build` para generar el manifest Vite requerido por las pruebas web preexistentes.

**Estado:** Finalizado.

## 2026-08-03 — IMP-026 (Cierre de ENG-025 — Programas educativos regionales)

### Completado

- Nuevo agregado `EducationalProgram`, con código normalizado, título, descripción y ciclo de vida `draft`, `published` y `archived`. La publicación exige al menos un curso y que todos los cursos referenciados estén publicados; el archivo vuelve inmutable el programa.
- Nuevo value object `ProgramAudience`, con criterios regionales opcionales y combinables por rango etario, etapas neutrales de licencia (`unlicensed`, `learner`, `licensed`, `professional`), contextos (`general`, `institutional`, `corporate`) y vehículos (`motorcycle`, `automobile`).
- Secuencia reutilizable de cursos existentes mediante `ProgramCourse`, con posición explícita, orden estable y rechazo de duplicados.
- Persistencia PostgreSQL normalizada en cinco tablas: `academic_programs`, `academic_program_courses`, `academic_program_license_stages`, `academic_program_contexts` y `academic_program_vehicle_types`. El repositorio Eloquent guarda el agregado completo en una transacción y lo reconstruye sin exponer modelos de infraestructura.
- Casos de uso registrados en `CommandBus`/`QueryBus` para crear y listar programas, cambiar audiencia, reemplazar cursos, publicar y archivar.
- Nuevos permisos `programs.manage` y `programs.view`: `SuperAdmin` recibe ambos; `InstitutionalAdmin`, `Teacher` y `Student` reciben únicamente consulta.
- API protegida con `auth:sanctum` y middleware de permisos:
  - `GET /api/v1/academic/programs`
  - `POST /api/v1/academic/programs`
  - `PATCH /api/v1/academic/programs/{programId}/audience`
  - `PUT /api/v1/academic/programs/{programId}/courses`
  - `POST /api/v1/academic/programs/{programId}/publish`
  - `POST /api/v1/academic/programs/{programId}/archive`
- Pruebas unitarias, de integración y Feature para audiencia, invariantes, persistencia, casos de uso, flujo HTTP completo, autenticación, permisos, validación y errores públicos.
- Endurecimiento final de invariantes: edades alineadas con el límite `smallint` de persistencia; cursos duplicados expuestos mediante `DUPLICATE_PROGRAM_COURSE`; reemplazo atómico de la secuencia de un programa publicado, que conserva al menos un curso y exige que todos sigan publicados; y traducción de colisiones concurrentes del código único a `PROGRAM_CODE_ALREADY_EXISTS` sin ocultar otras violaciones de base de datos.
- Diferido explícitamente: propiedad o personalización por organización; perfiles normativos por país y categorías legales de licencia; asociaciones adicionales entre cursos, competencias, evaluaciones o SIMUDRIVE más allá de la secuencia ordenada propia del programa; módulos y lecciones; versionado e historial curricular; inscripción y seguimiento de progreso.

### Validaciones

- `npm ci` ✅ (90 paquetes instalados, 0 vulnerabilidades).
- `npm run build` ✅ (Vite 7.3.6, 57 módulos transformados y `public/build/manifest.json` generado).
- `composer format` ✅ (314 archivos, sin cambios de estilo pendientes).
- `composer analyse` ✅ (238 archivos, sin errores).
- `composer quality` ✅ (222 pruebas, 730 aserciones).
- `php artisan route:list --path=api/v1/academic/programs` ✅ (6 rutas registradas).

**Estado:** Finalizado.

## 2026-08-04 — IMP-027 (Cierre de ENG-027 — Módulos y unidades)

### Completado

- Ampliación del agregado `Course` como único propietario del currículo regional, con entidades `CourseModule` y `CourseUnit`, identificadores UUID tipados (`CourseModuleId`/`CourseUnitId`) y códigos normalizados mediante `CurriculumCode`.
- Archivos productivos principales: `Domain/Aggregates/Course.php`, `Domain/Entities/CourseModule.php`, `Domain/Entities/CourseUnit.php`, `Application/UseCases/ReplaceCourseCurriculumHandler.php`, `Application/UseCases/GetCourseCurriculumHandler.php`, `Infrastructure/Persistence/Eloquent/Repositories/EloquentCourseRepository.php`, `Infrastructure/Persistence/Migrations/2026_08_03_000003_create_academic_course_curriculum_tables.php`, `Presentation/Http/Requests/ReplaceCourseCurriculumRequest.php`, `Presentation/Http/Controllers/CourseController.php` y `Presentation/Routes/api.php`, todos bajo `modules/Academic`.
- Estructura fija de dos niveles, curso → módulos → unidades, con código, título, descripción, objetivos opcionales, duración opcional, posición y prerrequisitos. El agregado valida posiciones consecutivas, unicidad de UUID y códigos, prerrequisitos no repetidos y referencias exclusivas a elementos anteriores del mismo currículo.
- Reemplazo atómico del currículo completo: una estructura inválida conserva intacto el estado anterior. Solo el currículo de los cursos `draft` es mutable; los cursos `published` y `archived` rechazan su reemplazo. La publicación exige al menos un módulo y una unidad por módulo.
- Compatibilidad legacy: el repositorio puede restaurar un curso publicado antiguo sin filas curriculares; un curso publicado que sí tenga estructura debe satisfacer las invariantes completas.
- Persistencia Eloquent transaccional integrada en `EloquentCourseRepository`, con modelos `CourseModuleModel`/`CourseUnitModel` y cuatro tablas nuevas:
  - `academic_course_modules`
  - `academic_course_units`
  - `academic_module_prerequisites`
  - `academic_unit_prerequisites`
- Sincronización que preserva UUID existentes, admite reordenamientos e intercambio de códigos sin colisiones transitorias, elimina nodos/pivotes obsoletos y evita transferir identificadores entre cursos. Las colisiones de propiedad se traducen al error público 409 `COURSE_CURRICULUM_ID_CONFLICT` sin ocultar otras excepciones de base de datos.
- Casos de uso `ReplaceCourseCurriculum` y `GetCourseCurriculum` registrados en `CommandBus`/`QueryBus`, con DTO de entrada y respuesta jerárquica tipados.
- API protegida por `auth:sanctum`, reutilizando los permisos existentes:
  - `GET /api/v1/academic/courses/{courseId}/curriculum` — `courses.view`
  - `PUT /api/v1/academic/courses/{courseId}/curriculum` — `courses.manage`
- Validación HTTP del payload completo, incluidos UUID escalares, códigos, límites de tamaño y duplicados por alcance; las invariantes de orden y prerrequisitos permanecen en el dominio.
- Pruebas unitarias, de integración y Feature para entidades, agregado, atomicidad, ciclo de vida, persistencia, reordenamiento, colisiones globales de UUID, casos de uso, validación, autenticación y permisos.
- Diferido explícitamente: lecciones, multimedia y accesibilidad del contenido (ENG-028); versionado, revisión y aprobación curricular (ENG-029); progreso y reglas de avance (ENG-035–037); reutilización de módulos/unidades entre cursos; interfaz web; y perfiles normativos por país.

### Validaciones

- `npm ci` ✅ (90 paquetes instalados, 0 vulnerabilidades).
- `npm run build` ✅ (Vite 7.3.6, 57 módulos transformados y `public/build/manifest.json` generado).
- `composer format` ✅ (345 archivos, sin cambios de estilo pendientes).
- `composer quality` final ✅ sobre `db2879d`: Pint 345 archivos; PHPStan 264 archivos, sin errores; 312 pruebas y 1013 aserciones. Este HEAD incluye los fixes de concurrencia entre publicación y reemplazo, canonicalización de prerrequisitos, límites agregados tempranos y restricciones `CHECK` de duración.
- Cobertura de persistencia ejecutada con SQLite real y compilación del SQL de la restricción `CHECK` mediante la gramática PostgreSQL; no se ejecutó PostgreSQL real. Una prueba de contención sobre PostgreSQL real queda como validación futura no bloqueante.
- `php artisan route:list --path=api/v1/academic/courses -v --except-vendor` ✅ (6 rutas registradas; todas protegidas por Sanctum y `courses.view`/`courses.manage` according to reading or writing).

**Estado:** Finalizado.

---

## 2026-08-08 — IMP-028 (Cierre de ENG-028 — Lecciones y contenido accesible)

### Completado

- **Modelo de Dominio**:
  - Nuevo agregado `UnitContent` dentro del módulo `Academic`, identificado por el UUID global de la unidad de curso (`CourseUnit`).
  - Entidad `Lesson` que contiene un título obligatorio, resumen opcional, duración estimada en minutos y un conjunto ordenado de bloques de contenido.
  - Entidad `ContentBlock` estructurada y tipada para 6 formatos de contenido con requisitos específicos de accesibilidad y validaciones de datos obligatorios por tipo:
    - `text`: texto Markdown seguro (sin HTML arbitrario).
    - `image`: URL HTTPS y texto alternativo obligatorio.
    - `video`: URL HTTPS, URL de subtítulos HTTPS y transcripción obligatoria.
    - `audio`: URL HTTPS y transcripción obligatoria.
    - `interactive`: URL HTTPS y texto o enlace alternativo accesible obligatorio.
    - `download`: URL HTTPS, nombre visible y tipo MIME obligatorio.
- **Validación de Invariantes**:
  - Posiciones secuenciales y consecutivas desde 1 para lecciones y bloques de contenido.
  - Unicidad global de UUID de lecciones y bloques.
  - Unicidad del código de lección dentro de la unidad de curso.
  - URLs externas restringidas a protocolo HTTPS y longitud máxima de 2048 caracteres.
  - Restricción de mutación atómica: solo los cursos en estado `draft` permiten reemplazo del contenido.
  - Validación de publicación del curso: publicar un curso exige cobertura completa (al menos una lección por unidad y al menos un bloque por lección).
- **Persistencia PostgreSQL**:
  - Creación de tres tablas normalizadas: `academic_unit_contents`, `academic_lessons` y `academic_lesson_blocks` con llaves foráneas y eliminación en cascada.
  - Repositorio `EloquentUnitContentRepository` que realiza sincronizaciones transaccionales atómicas, preservando UUID estables en reordenamientos y eliminando elementos huérfanos.
- **Casos de Uso y Capa de Aplicación**:
  - Caso de uso `ReplaceUnitContent` para reemplazo completo y atómico del contenido de una unidad.
  - Caso de uso `GetUnitContent` para consultar la estructura de lecciones y bloques de una unidad.
  - Registro de los comandos y consultas correspondientes en los buses de mensajes (`CommandBus` y `QueryBus`).
- **Controladores e Integración HTTP**:
  - Endpoint `PUT /api/v1/academic/courses/{courseId}/units/{unitId}/content` para reemplazo del contenido.
  - Endpoint `GET /api/v1/academic/courses/{courseId}/units/{unitId}/content` para consulta del contenido.
  - Ambos endpoints protegidos mediante `auth:sanctum` y los permisos `courses.manage` y `courses.view` respectivamente.
  - Validación temprana en `ReplaceUnitContentRequest`: topes agregados (100 lecciones, 200 bloques por lección, 1.000 bloques totales), URLs HTTPS, tipos discriminados por bloque y duplicados por casing.
  - Nota de integración: el merge original `aff9a3a` no incorporó esta capa de presentación; quedó recuperada e integrada en `aligned-active-main` en el commit `ee2fdc3`.
- **Suite de Pruebas**:
  - Pruebas unitarias de dominio para bloques de contenido, lecciones, e invariantes del agregado.
  - Pruebas de integración del repositorio de persistencia con transacciones, reordenamiento, y control de duplicados concurrentes.
  - Pruebas Feature de la API HTTP cubriendo autenticación, permisos, validación de payloads, y límites de tamaño.

### Validaciones

- `composer format` ✅ (389 archivos en formato de estilo correcto)
- `composer quality` final ✅ (512 pruebas, 1387 aserciones aprobadas y análisis estático con PHPStan sin errores en nivel 8)
- `docker compose exec app php artisan migrate` ✅ (todas las migraciones ejecutadas con éxito)
- `php artisan route:list` ✅ (8 rutas de cursos registradas, incluidas las dos de contenido)

**Estado:** Finalizado.

