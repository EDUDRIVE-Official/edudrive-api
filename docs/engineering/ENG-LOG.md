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

