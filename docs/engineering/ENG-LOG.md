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

## 2026-08-12 — IMP-032 (Cierre de ENG-032 — Intentos de evaluación)

### Completado

- **Modelo de Dominio**:
  - Se incorporó el agregado `ExamAttempt` como snapshot inmutable del `Exam` al iniciar cada intento, con configuración copiada (`title`, `duration_minutes`, `passing_score`, `shuffle_questions`, `feedback_mode`) y lista embebida de `AttemptQuestion`.
  - El intento maneja estados `in_progress`, `submitted` y `canceled`, respuestas por posición, cálculo básico de `score`, `total_points`, `percentage` y `passed`, prevención de doble envío, cancelación manual y timeout al `submit()` cuando expira `duration_minutes`.
  - Se añadieron `AttemptQuestion`, `ExamAttemptId`, `AttemptQuestionId`, `ExamAttemptStatus`, la excepción `InvalidExamAttempt`, y soporte `matches()` en respuestas tipadas para selección única, múltiple, verdadero/falso, asociación y ordenamiento.
- **Persistencia**:
  - Se creó la migración `2026_08_12_000001_create_academic_exam_attempt_tables` con tablas `academic_exam_attempts` y `academic_exam_attempt_questions`, índice parcial único para evitar dos intentos activos por `(exam_id, user_id)` y borrado en cascada de preguntas del intento.
  - Se implementaron `ExamAttemptModel`, `ExamAttemptQuestionModel` y `EloquentExamAttemptRepository`, con guardado transaccional (`delete + create` para el snapshot de preguntas) y rehidratación tipada de `correct_response` y `user_response` vía `QuestionResponseFactory`.
- **Capa de Aplicación**:
  - Se añadieron los comandos `StartExamAttempt`, `AnswerAttemptQuestion`, `SubmitExamAttempt` y `CancelExamAttempt`, las consultas `GetExamAttempt` y `ListExamAttempts`, y las respuestas `ExamAttemptResponse` / `ExamAttemptListItemResponse`.
  - Los handlers validan examen inexistente (`EXAM_NOT_FOUND`), límite de intentos (`EXAM_ATTEMPT_LIMIT_REACHED`), intento inexistente o ajeno (`EXAM_ATTEMPT_NOT_FOUND`) y doble envío (`EXAM_ATTEMPT_ALREADY_SUBMITTED`).
  - `AcademicServiceProvider` registra el repositorio `ExamAttemptRepository` y los 6 mensajes nuevos en `MessageHandlerRegistry`.
- **Presentación, permisos e integración HTTP**:
  - Se añadió `Permission::ViewExamAttempts`; `SuperAdmin`, `InstitutionalAdmin` y `Teacher` pueden listar o ver intentos de terceros, mientras que `Student` conserva acceso solo a sus propios intentos.
  - Se incorporó `ExamAttemptController`, requests `StartExamAttemptRequest` / `AnswerAttemptQuestionRequest` y 6 rutas bajo `auth:sanctum`: `GET /exam-attempts`, `GET /exam-attempts/{attemptId}`, `POST /exam-attempts`, `PUT /exam-attempts/{attemptId}/questions/{position}`, `POST /exam-attempts/{attemptId}/submit` y `POST /exam-attempts/{attemptId}/cancel`.
  - La respuesta de detalle oculta `is_correct`, `correct_response` y `explanation` cuando el usuario no tiene permiso ampliado y el examen usa `feedback_mode = none`.
- **Pruebas**:
  - Dominio/aplicación: `ExamAttemptHandlerTest` cubre inicio, respuestas, envío, ocultamiento para terceros, límite por intento activo y por `max_attempts`.
  - Persistencia: `EloquentExamAttemptRepositoryTest` valida ida y vuelta del agregado, conteo de completados, intento activo, filtrado y cascada.
  - Integración del contenedor: `AcademicServiceProviderExamAttemptTest` verifica el bind de `ExamAttemptRepository` y el registro de los handlers/queries.
  - Feature HTTP: `ExamAttemptTest` cubre start, answer, submit, rechazo de examen inexistente, acceso de terceros, feedback oculto, listado con permiso y autenticación requerida.

### Validaciones

- Pint ✅ (`php vendor/bin/pint`) — 529 archivos revisados; corrigió estilo en `ExamAttemptController` y `ExamAttemptHandlerTest`, y luego se revalidó la suite focalizada.
- PHPStan nivel 8 ✅ (`php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic/Presentation`) — sin errores tras explicitar el usuario autenticado no nulo en `ExamAttemptController`.
- Suites focalizadas ✅
  - `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php` → 14 pruebas / 56 aserciones.
  - `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php` → 10 pruebas / 19 aserciones.
  - `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php` → 4 pruebas / 15 aserciones.
  - `modules/Academic/Tests/Feature/ExamAttemptTest.php` → 9 pruebas / 43 aserciones.
  - Revalidación posterior a Pint: `ExamAttemptHandlerTest` + `EloquentExamAttemptRepositoryTest` + `ExamAttemptTest` → PASS.
- `php artisan route:list --path=academic/exam-attempts` ✅ (6 rutas registradas en `api/v1/academic/exam-attempts`).
- `php artisan migrate --force` + `migrate:status` ✅ (migración `2026_08_12_000001_create_academic_exam_attempt_tables` en estado `Ran`, batch 10).
- `php artisan test` (suite raíz) ✅ — 10 pruebas / 28 aserciones; la cobertura específica de ENG-032 se validó adicionalmente en las suites focalizadas anteriores.

**Estado:** Finalizado.

## 2026-08-12 — IMP-033 (Cierre de ENG-033 — Motor de calificación)

### Completado

- **Modelo de Dominio**:
  - Se introdujeron los objetos de grading `AttemptQuestionGrade`, `CompetencyGrade`, `GradingPolicy` y `GradingResult`, con invariantes explícitas para score, total de puntos, porcentaje y coherencia entre breakdowns y totales agregados.
  - Se implementó `ExamAttemptGrader` como servicio de dominio puro, capaz de calcular `score`, `total_points`, `percentage`, `passed`, breakdown por pregunta y breakdown por competencia a partir del snapshot del intento.
  - El grading quedó soportado por tipo de respuesta: `single_choice` y `true_false` siguen todo-o-nada; `multi_select`, `matching` y `ordering` admiten partial credit cuando la política lo permite; las penalizaciones quedan limitadas para no generar score negativo.
  - El snapshot `AttemptQuestion` se enriqueció con `competency_id`, permitiendo calificar el intento completo sin depender del banco vivo como fuente primaria de grading.
- **Persistencia**:
  - Se añadieron las migraciones `2026_08_12_000002_add_grading_breakdown_to_academic_exam_attempts` y `2026_08_12_000003_add_competency_id_to_academic_exam_attempt_questions`.
  - `academic_exam_attempts` ahora persiste `grading_breakdown` y `competency_results` como JSON materializado del resultado final.
  - `EloquentExamAttemptRepository` serializa y rehidrata `AttemptQuestionGrade` / `CompetencyGrade`, mantiene compatibilidad legacy cuando los JSON vienen `NULL`, y conserva un fallback seguro por lote para `competency_id` en snapshots históricos.
- **Capa de Aplicación**:
  - `SubmitExamAttemptHandler` ya no depende del cálculo inline básico; ahora construye `GradingPolicy`, invoca `ExamAttemptGrader` y aplica el `GradingResult` al agregado `ExamAttempt`.
  - El agregado `ExamAttempt` pasó a materializar `questionBreakdown()` y `competencyBreakdown()` al enviar el intento, preservando la cancelación por timeout como decisión semántica previa al grading.
  - La integración respeta que `is_correct` sigue significando corrección exacta, mientras el score parcial proviene del grader; esto evita confundir “recibió puntos” con “respuesta exacta”.
- **Presentación, permisos e integración HTTP**:
  - `ExamAttemptResponse` expone `grading_breakdown` y `competency_results` sin abrir endpoints nuevos.
  - `submit` devuelve grading detallado solo cuando el intento queda `submitted`; si termina `canceled` por timeout, no expone feedback ni breakdowns.
  - `show` expone grading y feedback ampliado solo cuando el intento está `submitted` y además pasa las reglas de visibilidad existentes (`feedback_mode` y/o permiso de lectura ampliada). Se documentó y blindó por pruebas la asimetría intencional entre `submit` y `show`.
- **Pruebas**:
  - Unidad de dominio: `ExamAttemptGraderTest` cubre todo-o-nada, partial credit por tipo, penalizaciones, deduplicación defensiva y clamp a `0`.
  - Dominio/agregado: `ExamAttemptTest` cubre aplicación de `GradingResult`, timeout, estados y score final.
  - Aplicación: `ExamAttemptHandlerTest` cubre integración del grader en submit, conservación de breakdowns y el path de timeout sin invocar grading.
  - Persistencia: `EloquentExamAttemptRepositoryTest` valida roundtrip del grading JSON, fallback legacy de `competency_id` y rehidratación segura del agregado.
  - Feature HTTP: `ExamAttemptTest` cubre grading en `submit`, ocultamiento en `show`, timeout, permisos y la asimetría `submit`/`show`.

### Validaciones

- Pint ✅ (`php vendor/bin/pint modules/Academic modules/Authorization`) — corrigió 7 archivos; luego se revalidó la suite focalizada completa.
- PHPStan nivel 8 ✅ (`php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic modules/Authorization`) — sin errores tras ajustar tipado de `ExamAttemptResponse`, `GradingResult` y el repositorio Eloquent de intentos.
- Suites focalizadas ENG-033 ✅ — `53 passed (284 assertions)`:
  - `modules/Academic/Tests/Unit/Domain/Services/ExamAttemptGraderTest.php`
  - `modules/Academic/Tests/Unit/Domain/Aggregates/ExamAttemptTest.php`
  - `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
  - `modules/Academic/Tests/Integration/EloquentExamAttemptRepositoryTest.php`
  - `modules/Academic/Tests/Feature/ExamAttemptTest.php`
- `php artisan migrate --force` ✅ — sin migraciones pendientes tras aplicar las migraciones de ENG-033.
- `php artisan route:list --path=academic/exam-attempts` ✅ — 6 rutas registradas y funcionales.
- `php artisan test` (suite raíz) ✅ — 10 pruebas / 28 aserciones; la cobertura específica de ENG-033 se validó adicionalmente en las suites focalizadas anteriores.

**Estado:** Finalizado.

## 2026-08-13 — IMP-034 (Implementación de ENG-034 — Examen teórico de conducción)

### Completado

- **Especialización sobre Academic, sin módulo paralelo**:
  - `ENG-034` se implementó reutilizando `Question`, `Exam`, `ExamAttempt` y `ExamAttemptGrader`, sin crear agregados nuevos para intentos o calificación.
  - Se mantuvo la arquitectura CQRS y la integración mediante `CommandBus` / `QueryBus` / `MessageHandlerRegistry`.
- **Banco teórico oficial**:
  - `Question` ahora soporta `source_kind`, `source_reference` y `license_categories`, con persistencia Eloquent, respuesta HTTP y validación de requests.
  - Las preguntas oficiales del banco teórico se filtran por categoría de licencia autorizada.
- **Examen teórico**:
  - `Exam` ahora soporta `kind`, `license_category`, `allow_partial_credit` y `apply_penalties`, con migración, roundtrip Eloquent, CQRS y exposición HTTP.
  - Los exámenes `theory` exigen `license_category`, mientras que los `standard` conservan el comportamiento previo.
- **Reglas de negocio teóricas**:
  - `CreateExamHandler` y `UpdateExamHandler` rechazan exámenes `theory` que incluyan preguntas `custom` o preguntas oficiales sin la categoría requerida.
  - Se añadió el error público `INVALID_THEORY_EXAM` (422) para estos rechazos.
- **Calificación por configuración del examen**:
  - `SubmitExamAttemptHandler` ya no construye una `GradingPolicy` fija para todos los casos.
  - Los exámenes `standard` conservan la política previa (`allowPartialCredit = true`, `applyPenalties = true`).
  - Los exámenes `theory` derivan la política desde `allow_partial_credit` y `apply_penalties` del examen asociado.
- **Recomendaciones de estudio**:
  - Se incorporaron `StudyRecommendationResponse` y `TheoryStudyRecommendationService`.
  - `ExamAttemptResponse` ahora puede exponer `study_recommendations` cuando el intento pertenece a un examen `theory`, está `submitted` y dispone de grading materializado.
  - Las recomendaciones se derivan de `competency_results` y `grading_breakdown`, ordenadas por peor desempeño y con evidencia mínima (`question_ids`).
- **API especializada**:
  - Se agregaron queries/handlers/controlador/rutas para:
    - `GET /api/v1/academic/theory-exams`
    - `GET /api/v1/academic/theory-exams/{examId}`
    - `POST /api/v1/academic/theory-exams/{examId}/start`
    - `GET /api/v1/academic/theory-attempts`
  - El inicio de simulación teórica delega al flujo ya existente de `ExamAttempt`, validando previamente que el examen sea `theory`.
  - El historial teórico filtra solo intentos asociados a exámenes `kind = theory` y soporta filtro por `license_category`; los usuarios con permiso ampliado pueden consultar terceros.

### Validaciones

- Pint ✅ (`php vendor/bin/pint modules/Academic modules/Authorization`) — 408 archivos revisados; 13 ajustes de estilo aplicados y revalidados después.
- PHPStan nivel 8 ✅ (`php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic modules/Authorization`) — sin errores tras ajustar tipado de `Question`, `QuestionResponse` y `StartTheoryExamSimulationRequest`.
- Suite focalizada ENG-034 ✅ — `129 passed (533 assertions)`:
  - `modules/Academic/Tests/Feature/QuestionTest.php`
  - `modules/Academic/Tests/Feature/ExamTest.php`
  - `modules/Academic/Tests/Feature/ExamAttemptTest.php`
  - `modules/Academic/Tests/Feature/TheoryExamTest.php`
  - `modules/Academic/Tests/Unit/Domain/Aggregates/QuestionTest.php`
  - `modules/Academic/Tests/Unit/Domain/Aggregates/ExamTest.php`
  - `modules/Academic/Tests/Unit/Application/ExamHandlerTest.php`
  - `modules/Academic/Tests/Unit/Application/ExamAttemptHandlerTest.php`
  - `modules/Academic/Tests/Unit/Application/ListTheoryExamAttemptsHandlerTest.php`
  - `modules/Academic/Tests/Integration/EloquentQuestionRepositoryTest.php`
  - `modules/Academic/Tests/Integration/EloquentExamRepositoryTest.php`
  - `modules/Academic/Tests/Integration/AcademicServiceProviderTheoryExamTest.php`

### Estado

- Implementado y verificado técnicamente en el árbol local.
- Pendiente de consolidación en commits coherentes junto al resto del trabajo local no versionado de `ENG-032` y `ENG-033`.

## 2026-08-16 — IMP-036 (Cierre de ENG-036 — Seguimiento de progreso)

### Completado

- **Modelo de dominio**:
  - Se agregó la entidad `LessonCompletion` (lección, fecha de completitud, tiempo invertido opcional, con validación de tiempo no negativo vía `InvalidLessonCompletion`).
  - Se agregó el agregado `EnrollmentProgress`, 1:1 con `Enrollment`, con `completeLesson()` idempotente (upsert por lección, sin duplicados) y accesores derivados (`completedLessonIds`, `totalTimeSpentMinutes`, `lastCompletedAt`).
  - Se agregó el servicio de dominio `CourseLessonCatalog`, que enumera los ids de lección de todas las unidades de un curso reutilizando `UnitContentRepository`, sin nuevos métodos de repositorio.
- **Persistencia**:
  - Se creó la migración `2026_08_15_000001_create_academic_enrollment_lesson_completions_table` con la tabla `academic_enrollment_lesson_completions` (una fila por lección completada, única por `enrollment_id`+`lesson_id`, FK en cascada a `academic_enrollments` y a `academic_lessons`).
  - Se implementaron `EnrollmentLessonCompletionModel` y `EloquentEnrollmentProgressRepository`, con `save()` como upsert masivo (`Model::upsert`) en vez de un loop por fila, evitando N+1 queries.
- **Capa de aplicación**:
  - Se añadió `EnrollmentProgressCalculator`, que combina `EnrollmentProgress` con el total de lecciones del curso (`CourseLessonCatalog`) y los intentos de examen enviados para ese curso (cruce `Exam`/`ExamAttempt` por `courseId`, sin N+1) para calcular porcentaje de avance, tiempo invertido, evaluaciones realizadas y última actividad.
  - Se añadieron `CompleteLessonCommand`/`CompleteLessonHandler` (valida enrollment propio y activo, lección perteneciente al curso, registra la completitud) y `GetEnrollmentProgressQuery`/`GetEnrollmentProgressHandler` (autorización por pertenencia o por el permiso ya existente `enrollments.view`).
  - `AcademicServiceProvider` registra el repositorio `EnrollmentProgressRepository` y los 2 mensajes nuevos en `MessageHandlerRegistry`.
- **Presentación e integración HTTP**:
  - Se agregó `EnrollmentProgressController` con 2 rutas bajo `auth:sanctum`: `POST /enrollments/{enrollmentId}/lessons/{lessonId}/complete` y `GET /enrollments/{enrollmentId}/progress`, sin middleware de permiso adicional (la autorización por pertenencia vive en los handlers, igual que `ExamAttemptController`).
  - No se agregó ningún permiso nuevo: se reutiliza `Permission::ViewEnrollments` para que terceros con acceso ampliado consulten el progreso de otro usuario.
  - Errores públicos: `ENROLLMENT_NOT_FOUND` (404, reutilizado), `INVALID_ENROLLMENT` (422, reutilizado) y `LESSON_NOT_FOUND` (404, nuevo).
- **Pruebas**:
  - Dominio: `LessonCompletionTest`, `EnrollmentProgressTest` (incluye guarda contra duplicados en `restore()` y prueba de máximo cronológico en `lastCompletedAt()`), `CourseLessonCatalogTest` (incluye traversal multi-módulo).
  - Persistencia: `EloquentEnrollmentProgressRepositoryTest` (roundtrip, upsert sin duplicar, progreso vacío, cascada al borrar el enrollment) y `AcademicServiceProviderEnrollmentProgressTest` (binding del repositorio y registro CQRS).
  - Aplicación: `EnrollmentProgressCalculatorTest`, `CompleteLessonHandlerTest`, `GetEnrollmentProgressHandlerTest`.
  - Feature HTTP: `EnrollmentProgressTest` (10 casos: autenticación, completar lección propia/ajena/inexistente/con enrollment inactivo, validación de `time_spent_minutes`, consulta de progreso propio/ajeno con y sin `enrollments.view`, enrollment inexistente).

### Validaciones

- Suite focalizada ENG-036 ✅ — `42 passed (81 assertions)` en los 9 archivos de test de dominio/persistencia/aplicación/feature listados arriba.
- Pint ✅ sobre los archivos de ENG-036 (sin issues; los 4 issues detectados en `pint --test modules/Academic tests/Pest.php` pertenecen a trabajo no relacionado de ENG-034/035 aún sin consolidar).
- PHPStan nivel 8 ✅ sobre los 17 archivos de código fuente de ENG-036 — sin errores.
- `php artisan route:list --path=academic/enrollments` ✅ — 10 rutas registradas (8 de ENG-035 + 2 nuevas de progreso).
- Implementado mediante subagent-driven-development: cada tarea del plan pasó por revisión de conformidad con la especificación y revisión de calidad de código independientes, con correcciones aplicadas donde se detectaron problemas (guarda contra duplicados en `EnrollmentProgress::restore()`, FK de `lesson_id` a `academic_lessons`, upsert masivo en vez de N+1 en `save()` y en `EnrollmentProgressCalculator::evaluationsFor()`).

**Estado:** Finalizado.

## 2026-08-16 — IMP-037 (Cierre de ENG-037 — Reglas de avance)

### Completado

- **Modelo de dominio**:
  - Se agregaron los value objects `UnitUnlockStatus` (unidad, completitud, desbloqueo y sus lecciones), `ModuleUnlockStatus` (módulo, completitud, desbloqueo y sus unidades) y `CurriculumUnlockStatus` (lista de módulos, con `isUnitUnlocked()` y `unitIdForLesson()`).
  - Se agregó el servicio de dominio `CourseCurriculumUnlockCalculator`, que deriva (sin persistir) el estado de todo el currículo de un curso combinando `Course` (prerrequisitos de módulo/unidad ya existentes) con `EnrollmentProgress` (lecciones completadas, ENG-036), reutilizando `UnitContentRepository` igual que `CourseLessonCatalog`. Reglas: un módulo se desbloquea si todos sus módulos prerrequisito están completos; una unidad se desbloquea si su módulo padre está desbloqueado y todas sus unidades prerrequisito están completas (pueden pertenecer a un módulo anterior); una unidad sin lecciones publicadas cuenta como completada.
- **Capa de aplicación**:
  - Se agregó la excepción `UnitLocked` (422, `UNIT_LOCKED`).
  - Se extendió `CompleteLessonHandler` (ENG-036): tras confirmar que la lección pertenece al curso, calcula el estado de desbloqueo y rechaza completar la lección si su unidad todavía está bloqueada.
  - Se añadieron `GetEnrollmentCurriculumStatusQuery`/`GetEnrollmentCurriculumStatusHandler` (misma autorización que `GetEnrollmentProgressHandler`: dueño del enrollment o permiso ya existente `enrollments.view`) y `CurriculumUnlockResponse`.
  - `AcademicServiceProvider` registra el nuevo query/handler en `MessageHandlerRegistry`.
- **Presentación e integración HTTP**:
  - Se agregó el método `curriculum` a `EnrollmentProgressController` y la ruta `GET /enrollments/{enrollmentId}/curriculum` bajo `auth:sanctum`, sin middleware de permiso adicional.
  - No se agregó ningún permiso nuevo: se reutiliza `Permission::ViewEnrollments`.
  - Errores públicos: `ENROLLMENT_NOT_FOUND` (404, reutilizado), `INVALID_ENROLLMENT` (422, reutilizado), `UNIT_LOCKED` (422, nuevo).
- **Pruebas**:
  - Dominio: `CourseCurriculumUnlockCalculatorTest` (4 casos: módulo/unidad sin prerrequisitos, desbloqueo de módulo dependiente de completitud del anterior, resolución de unidad por lección, unidad sin lecciones publicadas cuenta como completada).
  - Aplicación: extensión de `CompleteLessonHandlerTest` con el caso de unidad bloqueada; nuevo `GetEnrollmentCurriculumStatusHandlerTest` (4 casos: dueño, ajeno sin permiso, ajeno con permiso, enrollment inexistente).
  - Feature HTTP: extensión de `EnrollmentProgressTest` con 4 casos (consulta propia, ajena sin/con `enrollments.view`, enrollment inexistente).

### Correcciones sobre el plan original

- El plan referenciaba `ContentBlockFactory` en `Modules\Academic\Domain\Entities\ContentBlocks`; la clase real vive en `Modules\Academic\Domain\Services\ContentBlockFactory`. Se corrigió el import en los tests nuevos.
- El caso de test de `CompleteLessonHandlerTest` para unidad bloqueada, tal como estaba escrito en el plan, no habría reproducido el bloqueo: la unidad 1 no tenía ninguna lección publicada, por lo que se consideraba completada automáticamente (regla de "unidad sin lecciones = completada"), completando el módulo 1 y desbloqueando el módulo 2 sin que el estudiante completara nada. Se agregó una lección real (sin completar) a la unidad 1 para que el prerrequisito de módulo bloquee de verdad.

### Validaciones

- Suite focalizada ENG-037 ✅ — `32 passed (59 assertions)` en los 5 archivos de test de dominio/aplicación/integración/feature listados arriba.
- Pint ✅ sobre los 17 archivos de ENG-037 (sin issues; los 4 issues detectados en `pint --test modules/Academic` restantes pertenecen a trabajo no relacionado de ENG-034/035 aún sin consolidar).
- PHPStan nivel 8 ✅ sobre los 11 archivos de código fuente de ENG-037 — sin errores.
- `php artisan route:list --path=academic/enrollments` ✅ — 11 rutas registradas (10 de ENG-035/036 + 1 nueva de currículo).

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

## 2026-08-10 — IMP-029 (Cierre de ENG-029 — Publicación y versionado curricular)

### Completado

- **Modelo de Dominio**:
  - `CourseStatus` gana los estados intermedios `UnderReview` y `Approved` entre el borrador y la publicación.
  - Transiciones nuevas del agregado `Course`: `submitForReview()` (`draft` → `under_review`), `approve()` (`under_review` → `approved`), `sendBackToDraft()` (`under_review`/`approved` → `draft`) y `reopen()` (`published` → `draft`, conservando el historial y limpiando `publishedAt`).
  - `Course::publish()` ahora exige que el curso esté `approved`, conservando la validación de currículo completo y cobertura de contenido de ENG-028.
  - Entidad inmutable `CourseVersion` (identidad UUID técnica, `courseId`, `versionNumber` secuencial desde 1, `status` published/archived y snapshot canónico) y enumeración `CourseVersionStatus`.
  - Repositorio de dominio `CourseVersionRepository` (`save`, `allForCourse`, `findByNumber`, `nextVersionNumber`).
  - Excepciones públicas: `CourseReviewStateInvalid` (422, `COURSE_REVIEW_STATE_INVALID`) y `CourseCannotBeReopened` (422, `COURSE_CANNOT_BE_REOPENED`).
- **Persistencia PostgreSQL**:
  - Tabla `academic_course_versions` con `snapshot jsonb`, unicidad `(course_id, version_number)`, FK a `academic_courses` con `ON DELETE CASCADE` y PK UUID.
  - `CourseVersionModel` con casts (`snapshot` array, fechas inmutables) y `EloquentCourseVersionRepository` con historial ordenado por número de versión y siguiente número como máximo + 1.
- **Capa de Aplicación**:
  - Comandos y handlers `SubmitCourseForReview`, `Approve`, `SendBackToDraft` y `Reopen`, todos mediante mutaciones atómicas (`updateAtomically`) con curso inexistente traducido a 404.
  - `PublishCourseHandler` ampliado: captura el snapshot completo dentro del mismo lock de fila usado por `updateAtomicallyWithContentCoverage`, creando una `CourseVersion` con `nextVersionNumber` y `published_at`.
  - `CourseSnapshotBuilder` serializa datos generales del curso, módulos/unidades con prerrequisitos y lecciones/bloques de contenido por unidad.
  - Consultas `ListCourseVersions` y `GetCourseVersion` con `CourseVersionNotFound` (404, `COURSE_VERSION_NOT_FOUND`) para curso o versión inexistentes, más las respuestas `CourseStatusResponse`, `CourseVersionListItemResponse` y `CourseVersionResponse`.
- **Presentación e Integración HTTP**:
  - `POST /courses/{courseId}/submit-for-review`, `/approve`, `/send-back-to-draft` y `/reopen` bajo `auth:sanctum` + `courses.manage`.
  - `GET /courses/{courseId}/versions` y `GET /courses/{courseId}/versions/{versionNumber}` bajo `courses.view`.
  - Los endpoints de consulta existentes (currículo, contenido por unidad) siguen leyendo el borrador mutable.
- **Pruebas**:
  - Dominio: `CourseLifecycleTest` (transiciones válidas e inválidas, `publish` exige `approved`, reapertura y republicación) y `CourseVersionTest`.
  - Aplicación: `CourseLifecycleHandlerTest` (transiciones, 404/422, historial y snapshot) y ampliación de `CourseCurriculumHandlerTest` para verificar el snapshot capturado dentro de la mutación atómica.
  - Persistencia: `EloquentCourseVersionRepositoryTest` (ida y vuelta del snapshot, orden, siguiente número, unicidad, cascada, ausencia de N+1).
  - Feature HTTP: `CourseVersioningTest` (ciclo submit → approve → publish → reopen → publish v2, historial, snapshot detallado, casos 401/403/404/422 y lectura del borrador), con ajuste de los tests existentes `PublishCourseTest`, `ArchiveCourseTest`, `CourseCurriculumTest`, `CourseUnitContentTest` y `EducationalProgramTest` al flujo `approved` previo a publicación.

### Validaciones

- `composer format` ✅ (419 archivos en formato de estilo correcto, 0 pendientes)
- `composer quality` final ✅ (571 pruebas, 1635 aserciones aprobadas y análisis estático con PHPStan sin errores en nivel 8)
- `php artisan route:list` ✅ (14 rutas de cursos registradas, incluidas las 6 nuevas del ciclo de vida e historial)

**Estado:** Finalizado.

## 2026-08-10 — IMP-030 (Cierre de ENG-030 — Banco de preguntas)

### Completado

- **Modelo de Dominio**:
  - Enumeración `QuestionType` con los tipos `single_choice`, `multi_select`, `true_false`, `matching`, `ordering` y `situational`.
  - Sistema de respuesta tipada: interfaz `QuestionResponse` y cinco implementaciones (`SingleChoiceResponse`, `MultiSelectResponse`, `TrueFalseResponse`, `MatchingResponse`, `OrderingResponse`), todas rechazando claves desconocidas.
  - `QuestionResponseFactory` (aplicación) que construye la respuesta tipada desde un payload por tipo; para `situational` exige un tipo interno válido y media no vacía.
  - Agregado `Question` con valor propio `QuestionMedia` (tipo image/video/audio y URL estrictamente `https`), validación de consistencia entre respuesta y opciones (`ref_id` estable para referencias), puntaje ≥ 1 y prompt/explanation con límites.
  - Entidad `QuestionOption` con `refId`, posición y lado opcional (para asociación).
  - El agregado impide re-anclar la pregunta a otra competencia en la actualización.
  - Excepciones públicas: `InvalidQuestion` (422, `INVALID_QUESTION`), `InvalidQuestionScore`, más `QuestionNotFound` (404, `QUESTION_NOT_FOUND`) en capa de aplicación.
- **Persistencia PostgreSQL**:
  - Tablas `academic_questions` y `academic_question_options` con `response`/`media` JSON, `ref_id` único por pregunta en las opciones (para referencias estables de la respuesta), FK con `ON DELETE CASCADE` y PK UUID.
  - `QuestionModel`/`QuestionOptionModel` con casts, y `EloquentQuestionRepository` (`save`, `findById`, `all(competencyId)`, `delete`) con carga de opciones y desnormalización de `response`/`media`.
- **Capa de Aplicación**:
  - Comandos `CreateQuestion`, `UpdateQuestion`, `DeleteQuestion` y consultas `GetQuestion`, `ListQuestions` con sus handlers, y respuestas `QuestionResponse`/`QuestionListItemResponse` (el listado omite el detalle de la respuesta correcta).
  - `CreateQuestionHandler` valida que la competencia exista (404 si no), y el bus registra los 5 mensajes en `AcademicServiceProvider`.
- **Presentación e Integración HTTP**:
  - `QuestionController` con `index` (filtro por `competency_id`), `store`, `show`, `update` y `destroy`; requests `CreateQuestionRequest`/`UpdateQuestionRequest` con validación temprana y normalización `ref_id` → `refId` en el controller (patrón `min_age` → `minAge`).
  - 5 rutas bajo `auth:sanctum`: `GET /questions` y `GET /questions/{questionId}` bajo `questions.view`; `POST`, `PUT` y `DELETE` bajo `questions.manage`. `store` → 201, `destroy` → 204.
  - Permisos nuevos `questions.manage`/`questions.view`, con grant de gestión para SuperAdmin y de consulta para todos los roles.
- **Pruebas**:
  - Dominio: `QuestionResponseTest` y `QuestionOptionTest`.
  - Agregado: `QuestionTest` (creación por tipo, validación de respuesta/opciones/media, puntaje y límites).
  - Aplicación: `QuestionHandlerTest` (ciclo de vida completo, 404/422, filtrado por competencia).
  - Persistencia: `EloquentQuestionRepositoryTest` (ida y vuelta de todos los tipos, orden de opciones, filtrado, borrado y ausencia de N+1).
  - Feature HTTP: `QuestionTest` (12 casos: creación por tipo, true_false sin opciones, score 0 → 422, competencia inexistente → 404, listado filtrado, detalle, update, delete 204, 404 sobre pregunta inexistente, 401 sin token, Student lista pero 403 al crear, media no https → `INVALID_QUESTION`).

### Validaciones

- Pint ✅ (todos los archivos en formato de estilo correcto)
- PHPStan nivel 8 ✅ (sin errores; `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`)
- Suite completa ✅ (root: 10 pruebas/28 aserciones; Academic: 551 pruebas/1578 aserciones; Authorization e Identity/Organization/Audit/Foundation: 80 pruebas/206 aserciones)
- `php artisan route:list --path=academic/questions` ✅ (5 rutas en `api/v1/academic/questions`)
- `php artisan migrate --force` + `migrate:status` ✅ (migración `create_academic_questions_tables` en estado `Ran`)

**Estado:** Finalizado.

## 2026-08-11 — IMP-031 (Cierre de ENG-031 — Exámenes y cuestionarios)

### Completado

- **Modelo de Dominio**:
  - Enumeración `ExamFeedbackMode` con `none`, `after_submission` e `immediate`.
  - Value object `ExamId` (UUID) y entidad hija `ExamQuestion` (posición, `questionId`, puntaje).
  - Agregado `Exam` anclado a un curso, plantilla reutilizable sin estados de ciclo de vida, que valida título (≤180), descripción (≤2000), duración (≥1 o nula), intentos (≥1), puntaje de aprobación (1–100), al menos una pregunta, sin preguntas duplicadas y posiciones secuenciales 1..n. `create`/`restore`/`replace`.
  - Excepción pública `InvalidExam` (422, `INVALID_EXAM`) y contrato `ExamRepository` (`save`, `findById`, `all(?courseId)`, `delete`).
- **Persistencia PostgreSQL**:
  - Tablas `academic_exams` y `academic_exam_questions` (pivot normalizado con `position`/`points`), PK UUID, FKs con `ON DELETE CASCADE` y únicos `(exam_id, position)` y `(exam_id, question_id)`.
  - `ExamModel`/`ExamQuestionModel` con casts, y `EloquentExamRepository` con reemplazo atómico de preguntas en transacción, carga anticipada (sin N+1) y reconstrucción vía `Exam::restore`.
- **Capa de Aplicación**:
  - Comandos `CreateExam`, `UpdateExam`, `DeleteExam` y consultas `GetExam`, `ListExams` con sus handlers, y respuestas `ExamResponse`/`ExamListItemResponse` (el listado omite preguntas).
  - `CreateExamHandler` valida curso (`CourseNotFound`) y preguntas (`QuestionNotFound`); `ExamResponse::fromExam` enriquece cada pregunta con `ref_id`/`type` desde el banco. Bus registra los 5 mensajes en `AcademicServiceProvider`.
- **Presentación e Integración HTTP**:
  - `ExamController` con `index` (filtro por `course_id`), `store`, `show`, `update` y `destroy`; requests `CreateExamRequest`/`UpdateExamRequest` con validación temprana (`Enum` para `feedback_mode`, rangos para duración/intentos/puntaje), normalización `question_id` → `questionId` en el controller y `shuffle_questions` vía `$request->boolean` (sin inversión de strings).
  - 5 rutas bajo `auth:sanctum`: `GET /exams` y `GET /exams/{examId}` bajo `exams.view`; `POST`, `PUT` y `DELETE` bajo `exams.manage`. `store` → 201, `destroy` → 204.
  - Permisos nuevos `exams.manage`/`exams.view`, con grant de gestión para SuperAdmin y de consulta para todos los roles.
- **Pruebas**:
  - Agregado: `ExamTest` (creación con configuración, rechazos de invariantes y `replace` conservando id/curso).
  - Aplicación: `ExamHandlerTest` (ciclo de vida completo, 404 de curso/pregunta/examen, filtrado por curso).
  - Persistencia: `EloquentExamRepositoryTest` (ida y vuelta con preguntas ordenadas, valores nulos, reemplazo atómico al re-guardar, filtrado y borrado en cascada).
  - Feature HTTP: `ExamTest` (14 casos: creación, curso inexistente → 404, pregunta inexistente → 404, validación de rangos → 422, sin preguntas y sin clave `questions` → 422 `INVALID_EXAM`, duplicados → `INVALID_EXAM`, listado filtrado 3/2, detalle con preguntas en orden y `type`, update, delete 204, 404 sobre examen inexistente, 401 sin token, Student lista pero 403 al crear).

### Validaciones

- Pint ✅ (todos los archivos en formato de estilo correcto)
- PHPStan nivel 8 ✅ (sin errores; `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`)
- Suite completa ✅ (root: 10 pruebas/28 aserciones; Academic: 584 pruebas/1704 aserciones; Authorization e Identity/Organization/Audit/Foundation: 82 pruebas/214 aserciones)
- `php artisan route:list --path=academic/exams` ✅ (5 rutas en `api/v1/academic/exams`)
- `php artisan migrate --force` + `migrate:status` ✅ (migración `create_academic_exams_tables` en estado `Ran`)

**Estado:** Finalizado.

## 2026-08-26 — IMP-038 (Cierre de ENG-038 — Learning Record Store interno)

### Completado

- **Modelo de dominio**:
  - Nuevo módulo `Modules\Learning` con la entidad inmutable `LearningEvent` (`enrollmentId`, `userId`, `courseId`, `verb`, `subjectId`, `occurredAt`, `evidence`), el value object `LearningEventId` (UUID normalizado) y el enum `LearningVerb` (`lesson_completed`, `exam_attempt_submitted`).
  - Contrato de dominio `LearningEventRepository` (`record()`, `findByEnrollmentId()`).
- **Persistencia**:
  - Migración `2026_08_16_000001_create_learning_events_table` con la tabla `learning_events` (FK en cascada a `academic_enrollments`, `users` y `academic_courses`; `evidence` como JSON; índice `(enrollment_id, occurred_at)`).
  - `LearningEventModel` y `EloquentLearningEventRepository`, con lectura ordenada del más reciente al más antiguo.
- **Capa de aplicación e integración entre módulos**:
  - `LearningEventEntry` (DTO) y el contrato `LearningEventRecorder`, con `DefaultLearningEventRecorder` como implementación por defecto — mismo patrón que `Identity` → `Audit` (`AuditLogger`).
  - `LearningServiceProvider` registra el repositorio, el recorder, la consulta `GetEnrollmentLearningEventsQuery`/`GetEnrollmentLearningEventsHandler` (autorización por pertenencia al enrollment o permiso ya existente `enrollments.view`, reutilizando `EnrollmentRepository`/`EnrollmentNotFound` de `Academic`) y las rutas del módulo.
  - `CompleteLessonHandler` (Academic, ENG-036/037) ahora recibe `LearningEventRecorder` y registra `lesson_completed` tras completar una lección, con `time_spent_minutes` como evidencia.
  - `SubmitExamAttemptHandler` (Academic) recibe `EnrollmentRepository` y `LearningEventRecorder` como colaboradores opcionales (`null` por defecto, mismo patrón que `$grader`/`$exams`/`$recommendations` en este archivo) y registra `exam_attempt_submitted` con `score`/`total_points`/`percentage`/`passed` como evidencia, resolviendo el enrollment del alumno para el curso del examen vía `EnrollmentRepository::findActiveOrPendingFor()` (ENG-035). Si no hay enrollment resoluble para ese curso/usuario, no falla y simplemente no registra el evento.
  - Acoplamiento bidireccional real e intencional entre `Academic` y `Learning` (escritura: `Academic` → `Learning`; lectura de autorización: `Learning` → `Academic`), documentado en `docs/plans/2026-08-16-learning-record-store-eng038-implementation.md`.
- **Presentación e integración HTTP**:
  - `LearningEventController` y ruta `GET /api/v1/academic/enrollments/{enrollmentId}/learning-events` bajo `auth:sanctum`, sin permiso nuevo (reutiliza `Permission::ViewEnrollments`, igual que `EnrollmentProgressController`).
- **Pruebas**:
  - Dominio: `LearningEventTest`, `LearningEventIdTest`.
  - Persistencia: `EloquentLearningEventRepositoryTest` (orden, aislamiento por enrollment).
  - Integración: `LearningServiceProviderTest` (bindings en el contenedor).
  - Aplicación: `GetEnrollmentLearningEventsHandlerTest` (dueño, ajeno con/sin permiso, enrollment inexistente); extensión de `CompleteLessonHandlerTest` (spy de recorder) y de `ExamAttemptHandlerTest` (2 casos nuevos: registro con enrollment resoluble y no-fallo/no-registro sin enrollment resoluble).
  - Feature HTTP: `LearningEventTest` (propios, ajenos con/sin `enrollments.view`, enrollment inexistente).

### Nota sobre el historial de commits

- El commit de la Task 7 (`262991a`, `feat(academic): record learning event on exam attempt submission`) consolida, además del cambio de esta historia, la deuda de consolidación de ENG-032/033/034 que ya afectaba a `SubmitExamAttemptHandler.php` y `ExamAttemptHandlerTest.php` (nunca comiteados hasta ahora): motor de calificación, intentos de examen y examen teórico. Decisión explícita del usuario, documentada en el propio mensaje del commit; el resto de archivos de esa deuda (`Enrollment`, `Question`/`Exam` theory metadata, etc.) sigue sin commitear y queda fuera de alcance de ENG-038.

### Validaciones

- Suite focalizada ENG-038 ✅ — `40 passed (118 assertions)` (`modules/Learning` completo + `CompleteLessonHandlerTest` + `ExamAttemptHandlerTest`).
- Suite Feature adicional ✅ — `EnrollmentProgressTest` (16 casos), `ExamAttemptTest` (13 casos) y `TheoryExamTest` (4 casos) sin regresiones.
- Pint ✅ sin issues sobre los archivos de ENG-038.
- PHPStan nivel 8 ✅ sin errores sobre `modules/Learning`, `CompleteLessonHandler.php` y `SubmitExamAttemptHandler.php`.
- `php artisan route:list --path=academic/enrollments` ✅ — 12 rutas registradas (11 previas + `learning-events`).
- `php artisan migrate --force` ✅ — migración `create_learning_events_table` en estado `Ran`.

**Estado:** Finalizado.

## 2026-08-26 — Consolidación de deuda de commits (ENG-032/033/034/035)

### Contexto

Desde sesiones previas, ENG-032 (Intentos de evaluación), ENG-033 (Motor de calificación) y ENG-034 (Examen teórico de conducción) estaban implementados y validados técnicamente, pero su código nunca se había comiteado (ver notas de IMP-032/033/034 y `docs/engineering/SESION.md`). De ENG-035 (Inscripciones) solo se había comiteado la capa de presentación HTTP (`EnrollmentController`, requests, rutas); el dominio, la aplicación y la persistencia de `Enrollment` seguían sin commitear. Esta sesión consolidó toda esa deuda.

### Completado

- **Validación previa a comitear**: `php vendor/bin/pint modules/Academic` (458 archivos; corrigió 4 issues de estilo en `CreateBulkEnrollmentsHandler.php` y 3 tests, revalidados en verde) y `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G modules/Academic` (sin errores).
- **Commit `1d6d90b`** — ENG-032 + ENG-033 + ENG-034 juntos (92 archivos): las tres historias comparten los mismos archivos base (`Exam.php`, `ExamAttempt.php`, `Question.php`, `AttemptQuestion.php` y sus capas de persistencia/HTTP), construidos como extensiones sucesivas del mismo código en una sesión previa continua. Separarlas en tres commits históricos distintos después del hecho habría requerido dividir hunks dentro de los mismos archivos sin información confiable de límites entre tareas, con riesgo real de dejar un commit intermedio con código roto (una clase con un método a medio escribir). Se documentó esta decisión explícitamente en el mensaje del commit.
- **Commit `e3e2186`** — ENG-035, dominio/aplicación/persistencia de `Enrollment` (38 archivos): agregado `Enrollment`, `EnrollmentId`, enums `EnrollmentStatus`/`EnrollmentSource`, `EnrollmentRepository`, casos de uso de creación (individual/masiva/institucional) y ciclo de vida (activar/completar/cancelar), consultas, y persistencia Eloquent en `academic_enrollments`. No tocó la capa de presentación, ya comiteada.
- **Commits de documentación**: `3fbd2ff` (diseño/plan de ENG-033/034), `d32fb80` (diseño/plan de ENG-035) y `50d8035` (corrección menor de un nombre de método en el plan de ENG-036, hallazgo incidental sin relación con esta consolidación).
- **Roadmap actualizado**: ENG-034 pasa de "En validación" a **Completado**; ENG-035 pasa de "Pendiente" a **Completado**; ambos con nota explicando la consolidación y referencia a los commits.
- Explícitamente fuera de alcance de esta consolidación: el resto de la deuda documentada en `SESION.md` que no correspondía a estas cuatro historias (si la hay), y cualquier archivo ajeno a `modules/Academic` que pudiera aparecer como no trackeado (`.claude/`, `games/`, etc.), que no se tocaron.

### Validaciones

- Pint ✅ sobre todo `modules/Academic` (458 archivos, sin issues tras la corrección).
- PHPStan nivel 8 ✅ sobre todo `modules/Academic`, sin errores.
- Suite focalizada previa (misma sesión, antes de comitear) ✅ para los archivos recién corregidos por Pint (`BulkEnrollmentHandlerTest`, `EnrollmentHandlerTest`, `TheoryExamHandlerTest` — 10 pruebas / 35 aserciones) y para toda la funcionalidad de ENG-032/033/034/035 vía las suites Feature/Integration/Unit ya verificadas en sesiones anteriores y no modificadas en esta.

**Estado:** Finalizado.

## 2026-08-26 — IMP-039 (Cierre de ENG-039 — Recomendaciones de aprendizaje)

### Alcance acordado con el usuario

Incluido: recomendación de próxima lección, refuerzo de competencias agregado por curso, recomendación de reintentar exámenes reprobados. Diferido explícitamente: integración con SIMUDRIVE (sistema externo, fuera de este repositorio) y recomendaciones a nivel de pregunta individual más allá de la evidencia que ya aporta el refuerzo de competencias. Rutas adaptativas siguen diferidas desde ENG-037. Detalle en `docs/plans/2026-08-26-recomendaciones-aprendizaje-eng039-design.md`.

### Completado

- **Servicio de aplicación `EnrollmentLearningRecommendationService`** (`modules/Academic/Application/Services`), sin persistencia nueva — todo se deriva en memoria a partir de `Course`, `EnrollmentProgress`, `Exam` y `ExamAttempt` ya existentes:
  - Próxima lección: recorre `CourseLessonCatalog::lessonIdsFor()` en orden curricular y devuelve la primera lección no completada cuya unidad esté desbloqueada según `CourseCurriculumUnlockCalculator` (reutilizados sin modificar); `null` si el curso ya está completo.
  - Refuerzo de competencias: generaliza la lógica de `TheoryStudyRecommendationService` (breakdown por competencia con evidencia de `question_ids`) a través de todos los exámenes del curso, usando únicamente el intento **enviado más reciente** por examen (mismo patrón sin N+1 que `EnrollmentProgressCalculator::evaluationsFor()`: se listan los exámenes del curso una vez y se cruzan en memoria contra los intentos del usuario). Ordenado peor-primero, acotado a un máximo fijo de 5. Reutiliza `StudyRecommendationResponse` sin crear un DTO nuevo.
  - Exámenes para reintentar: por cada examen del curso, si el intento más reciente no aprobó, quedan intentos disponibles (`countCompletedFor() < maxAttempts()`) y no hay un intento activo (`findActiveFor()`), se recomienda reintentar. Nuevo DTO `RetryableExamResponse`.
- **CQRS**: `GetEnrollmentLearningRecommendationsQuery`/`GetEnrollmentLearningRecommendationsHandler`, misma autorización que `GetEnrollmentProgressHandler`/`GetEnrollmentCurriculumStatusHandler` (dueño del enrollment o permiso ya existente `enrollments.view`, sin permiso nuevo). Registrado en `AcademicServiceProvider`.
- **HTTP**: `EnrollmentProgressController::recommendations()` + `GET /enrollments/{enrollmentId}/recommendations` bajo `auth:sanctum`, junto a `progress`/`curriculum` en el mismo controlador. Error público reutilizado: `ENROLLMENT_NOT_FOUND` (404); sin errores nuevos (el endpoint no recibe payload).
- **Pruebas**:
  - Aplicación: `EnrollmentLearningRecommendationServiceTest` (6 casos: sin actividad, próxima lección salta la completada, null al completar todo, usa el intento más reciente por examen omitiendo competencias con desempeño perfecto, reintentos con exclusión por aprobado/agotado/intento activo, orden peor-primero a través de varios exámenes) y `GetEnrollmentLearningRecommendationsHandlerTest` (4 casos de autorización, mismo patrón que el resto de handlers de enrollment).
  - Integración: extensión de `AcademicServiceProviderEnrollmentProgressTest` con el registro del nuevo handler.
  - Feature HTTP: extensión de `EnrollmentProgressTest` con 4 casos (propias, ajenas con/sin `enrollments.view`, enrollment inexistente).
- **Corrección durante el desarrollo**: la primera versión de "intento más reciente por examen" comparaba `submittedAt()` entre intentos, pero dos intentos enviados en la misma prueba pueden compartir el mismo segundo de timestamp y producir un empate que conservaba el intento equivocado. Se corrigió aprovechando que `ExamAttemptRepository::all()` ya ordena por `created_at` ascendente: basta con sobrescribir por examen al iterar, sin comparar timestamps.

### Validaciones

- Suite focalizada ENG-039 ✅ — `EnrollmentLearningRecommendationServiceTest` (6/6), `GetEnrollmentLearningRecommendationsHandlerTest` (4/4), extensión de `AcademicServiceProviderEnrollmentProgressTest` y de `EnrollmentProgressTest` (20/20 tras la extensión), todas en verde.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=academic/enrollments` ✅ — 13 rutas registradas (12 previas + `recommendations`).

**Estado:** Finalizado.

## 2026-08-26 — IMP-040 (Cierre de ENG-040 — Núcleo del Pasaporte Vial)

### Alcance acordado con el usuario

Estado (`active`/`suspended`/`revoked`) y nivel numérico (solo sube mientras está `active`) en el núcleo. "Historial formativo" en este alcance es el historial propio de cambios de estado/nivel del pasaporte, no la lista de cursos/evaluaciones (eso es ENG-041). Diferido explícitamente: vigencia/expiración, agregación de evidencias (ENG-041), cálculo automático de confianza/nivel (ENG-042), reemisión de un pasaporte revocado. Detalle en `docs/plans/2026-08-26-nucleo-pasaporte-vial-eng040-design.md`.

### Completado

- **Módulo nuevo `Modules\RoadPassport`**, siguiendo `ENG-003` al pie de la letra: bootstrap con `RoadPassportServiceProvider`, endpoint de estado `GET /api/v1/road-passport/status`, registrado en `bootstrap/providers.php`.
- **Dominio**: agregado `RoadPassport` (`RoadPassportId`, `userId`, `RoadPassportStatus`, `level: int`, `issuedAt`, `history: list<PassportHistoryEntry>`).
  - `suspend()` solo desde `Active`; `reactivate()` solo desde `Suspended`; `revoke()` desde `Active`/`Suspended`, terminal (rechaza cualquier transición posterior, incluido `changeLevel()`); `changeLevel()` exige `Active` y un nivel estrictamente mayor al actual. Cada transición y cambio de nivel agrega una entrada al historial (`PassportHistoryEntry::statusChanged()`/`::levelChanged()`).
  - Excepciones de dominio `InvalidRoadPassportTransition` y `InvalidRoadPassportLevel` (422).
- **Persistencia**: tablas `road_passports` (`user_id` único, FK a `users`) y `road_passport_history_entries` (FK cascada a `road_passports`). `EloquentRoadPassportRepository::save()` transaccional, borra y reinserta el historial completo en cada guardado (mismo patrón que `EloquentExamAttemptRepository` con sus preguntas).
- **Aplicación (CQRS)**: `IssueRoadPassportCommand` (rechaza un segundo pasaporte para el mismo usuario con `RoadPassportAlreadyExists`, 409), `SuspendRoadPassportCommand`, `ReactivateRoadPassportCommand`, `RevokeRoadPassportCommand`, `ChangeRoadPassportLevelCommand` (todos con `RoadPassportNotFound`, 404, si no existe), `GetRoadPassportQuery` (dueño o permiso ampliado, mismo patrón que `GetEnrollmentProgressHandler`) y `GetMyRoadPassportQuery` (resuelve el pasaporte del usuario autenticado por su `userId`, sin necesitar conocer el `roadPassportId`).
- **Autorización**: permisos nuevos `road_passports.manage`/`road_passports.view`, mismo patrón de concesión que `enrollments.manage`/`enrollments.view` (SuperAdmin e InstitutionalAdmin ambos; Teacher solo view; Student ninguno, accede al propio por pertenencia).
- **HTTP**: `RoadPassportController` con `POST /road-passport` (emitir), `GET /road-passport/me` (propio), `GET /road-passport/{id}` (dueño o `road_passports.view`), `POST /road-passport/{id}/suspend|reactivate|revoke` y `PUT /road-passport/{id}/level`, todos bajo `auth:sanctum`, mutaciones bajo `road_passports.manage`.
- **Pruebas**: `RoadPassportTest` (dominio, 12 casos), `EloquentRoadPassportRepositoryTest` (persistencia, 5 casos, incluida la sustitución del historial en vez de duplicarlo), `RoadPassportServiceProviderTest` (registro CQRS), `RoadPassportHandlerTest` (aplicación, 10 casos con repositorio en memoria), `RoadPassportTest`/`RoadPassportStatusTest` (Feature, 13 casos: autorización, transiciones, 422, 404, 409).
- **Corrección durante el desarrollo**: colisión de nombres entre el helper `persistedRoadPassportFor()` declarado tanto en el test de aplicación (con repositorio en memoria) como en el test Feature (con base de datos real) — Pest carga todos los archivos de test en el mismo proceso PHP, así que dos funciones globales con el mismo nombre en archivos distintos producen un error fatal de redeclaración. Se renombró el helper del test Feature a `persistedRoadPassportFeature()`.

### Validaciones

- Suite completa del módulo ✅ — `42 passed (112 assertions)` (dominio, aplicación, persistencia, integración del provider y feature HTTP).
- Regresión ✅ sobre Authorization/Identity/Organization/Foundation/Learning tras el cambio de permisos — `105 passed (273 assertions)`, sin fallos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=road-passport` ✅ — 8 rutas registradas.

**Estado:** Finalizado.

## 2026-08-26 — IMP-041 (Cierre de ENG-041 — Evidencias del Pasaporte Vial)

### Alcance acordado con el usuario

Solo dos tipos de evidencia con modelo de dominio real hoy: `course_completed` (`Enrollment` completado) y `exam_passed` (`ExamAttempt` aprobado), registrados de forma reactiva y automática. Diferido explícitamente: prácticas y simulaciones (dependen de SIMUDRIVE, sistema externo), certificaciones (sin concepto de dominio modelado), evidencias externas autorizadas (no existe mecanismo de ingesta externa) y el cálculo automático de confianza/nivel a partir de la evidencia (ENG-042). Detalle en `docs/plans/2026-08-26-evidencias-pasaporte-vial-eng041-design.md`.

### Completado

- **Dominio**: enum `EvidenceType` (`course_completed`, `exam_passed`) y VO `Evidence` (`type`, `subjectId`, `courseId`, `occurredAt`, `details: array`). `RoadPassport` gana `evidence: list<Evidence>` y `recordEvidence(Evidence $evidence): void`, **idempotente** por `type`+`subjectId` (no duplica si ya existe una entrada para el mismo sujeto), sin exigir ningún estado particular del pasaporte (se registra igual si está `suspended`, es un hecho histórico, no una transición). `restore()` gana el parámetro `evidence`.
- **Persistencia**: tabla nueva `road_passport_evidence` (FK cascada a `road_passports`, `course_id` con FK a `academic_courses` — mismo patrón cross-módulo ya usado por `learning_events.course_id` en ENG-038). `EloquentRoadPassportRepository::save()` extiende su transacción existente: borra y reinserta también las filas de evidencia.
- **Aplicación — registro reactivo**: `EvidenceEntry` (DTO), contrato `RoadPassportEvidenceRecorder` y `DefaultRoadPassportEvidenceRecorder` (resuelve el pasaporte por `userId`; si el usuario no tiene uno emitido, no falla, simplemente no registra nada).
  - `CompleteEnrollmentHandler` (Academic) recibe `?RoadPassportEvidenceRecorder $evidenceRecorder = null` y registra `course_completed` tras completar el enrollment.
  - `SubmitExamAttemptHandler` (Academic) recibe `?RoadPassportEvidenceRecorder $evidenceRecorder = null` como séptimo parámetro opcional (no rompe las llamadas posicionales existentes) y registra `exam_passed` solo cuando `attempt->passed() === true`, resolviendo el curso vía `EnrollmentRepository::findActiveOrPendingFor()` (mismo patrón que el registro de `LearningEvent` ya existente en el mismo handler).
  - Acoplamiento bidireccional igual de intencional que `Academic`↔`Learning` en ENG-038: `Academic` depende de `RoadPassportEvidenceRecorder` (escritura); `RoadPassport` no depende de `Academic` en absoluto.
- **Exposición**: `RoadPassportResponse` agrega el campo `evidence`, visible en `GET /road-passport/me` y `/{id}` — sin endpoint ni permiso nuevo.
- **Pruebas**: extensión de `RoadPassportTest` (dominio, 3 casos nuevos: registro en orden, deduplicación, registro independiente del estado), extensión de `EloquentRoadPassportRepositoryTest` (persistencia, 3 casos: guardado/recuperación con detalles, sustitución en vez de duplicado, cascada), extensión de `RoadPassportServiceProviderTest` (binding del recorder), extensión de `EnrollmentLifecycleHandlerTest` (evidencia al completar matrícula, con spy) y de `ExamAttemptHandlerTest` (evidencia al aprobar un intento con ambas preguntas correctas, y ausencia de evidencia si no aprueba).

### Validaciones

- Suite completa del módulo ✅ — `49 passed (124 assertions)`.
- Regresión ✅ sobre `EnrollmentTest`, `ExamAttemptTest`, `TheoryExamTest` y `EnrollmentProgressTest` (Feature) tras modificar los dos handlers de Academic — `55 passed (218 assertions)`, sin fallos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.

**Estado:** Finalizado.

## 2026-08-26 — IMP-042 (Cierre de ENG-042 — Competency Trust Model)

### Alcance acordado con el usuario

Un `trust_score` global para todo el pasaporte (no por competencia — la evidencia actual es a nivel de curso/examen, sin desagregación por competencia). Diferido explícitamente: desagregación por competencia individual, validez/expiración de evidencia individual (sigue diferida desde ENG-040) y persistencia/historial del score (se recalcula siempre al vuelo). Detalle en `docs/plans/2026-08-26-competency-trust-model-eng042-design.md`.

### Completado

- **Servicio de dominio puro `RoadPassportTrustCalculator`** (`modules/RoadPassport/Domain/Services`), sin dependencias de infraestructura — mismo espíritu que `CourseCurriculumUnlockCalculator` en Academic. `calculate(RoadPassport $passport, DateTimeImmutable $now): int`:
  - **Fuente de evidencia**: peso base fijo por `EvidenceType` (`exam_passed` = 15, `course_completed` = 10).
  - **Recencia / degradación temporal**: factor de decaimiento 1.0 si la antigüedad es ≤ 90 días; decae linealmente hasta un piso de 0.2 a partir de 365 días (nunca llega a cero — evidencia vieja sigue contando algo).
  - **Consistencia**: multiplicador `min(1.0, 0.5 + 0.1 × cantidad de evidencia)` — una sola pieza da 0.6, cinco o más piezas topan en 1.0 (retornos decrecientes, no suma lineal sin límite).
  - Resultado: `min(100, round(Σ pesos ponderados × multiplicador))`; sin evidencia, `0`.
- **Exposición**: `RoadPassportResponse::fromRoadPassport()` recibe un `?DateTimeImmutable $now = null` opcional y calcula `trust_score` internamente instanciando `new RoadPassportTrustCalculator` (puro, sin inyección por contenedor — mismo patrón que `new ExamAttemptGrader`/`new TheoryStudyRecommendationService` como valores por defecto en otros handlers). Campo `trust_score` agregado a la respuesta existente, visible en `GET /road-passport/me` y `/{id}`, sin endpoint ni permiso nuevo.
- **Pruebas**: `RoadPassportTrustCalculatorTest` (7 casos: cero sin evidencia, fuente exam > curso, sin degradar hasta 90 días, degradación lineal a mitad de camino, piso mínimo a partir de un año, multiplicador de consistencia con retornos decrecientes, tope en 100); extensión de `RoadPassportHandlerTest` (trust_score en 0 al emitir sin evidencia, y mayor que 0 tras registrar evidencia).

### Validaciones

- Suite completa del módulo ✅ — `57 passed (135 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=road-passport` ✅ — 8 rutas registradas (sin cambios, no se agregó ningún endpoint nuevo).

**Estado:** Finalizado.

## 2026-08-26 — IMP-043 (Cierre de ENG-043 — Credenciales y certificaciones)

### Alcance acordado con el usuario

Emisión **administrativa/manual** de certificados (permiso `certifications.manage`, mismo patrón que la emisión de `RoadPassport` en ENG-040) para un usuario+curso, con código de validación generado al emitir, vigencia opcional (`expiresAt`), revocación terminal e historial de cambios de estado — como credencial independiente, no como parte del Pasaporte Vial. Diferido explícitamente: emisión automática disparada por evidencia del Pasaporte Vial (`course_completed`), verificación pública por código (bullet propio de ENG-044) y reemisión de un certificado revocado (mismo criterio que ENG-040 con el pasaporte). Detalle en `docs/plans/2026-08-26-credenciales-certificaciones-eng043-design.md`.

### Completado

- **Módulo nuevo `Modules\Certification`** (`modules/Certification`), siguiendo ENG-003 al pie de la letra: capas Domain/Application/Infrastructure/Presentation, `CertificationServiceProvider` registrado en `bootstrap/providers.php`, endpoint de estado público `GET /api/v1/certification/status`.
- **Dominio**: `CertificateId` (VO UUID, mismo patrón que `RoadPassportId`); `ValidationCode` (VO) — código aleatorio de 12 caracteres alfanuméricos en mayúsculas agrupado `XXXX-XXXX-XXXX`, excluyendo caracteres ambiguos (`0`, `O`, `1`, `I`) para legibilidad humana, con `generate()` y `fromString()` (valida formato al reconstruir); `CertificateStatus` (enum `Issued`/`Revoked` — terminal, sin `Suspended` a diferencia de `RoadPassport`: un certificado revocado no se reactiva); `CertificateHistoryEntry` (VO de cambios de estado); agregado `Certificate` (`create()`, `restore()`, `revoke()` — rechaza si ya está `Revoked` vía `InvalidCertificateTransition`, 422).
- **Persistencia**: tablas `certificates` (PK UUID, `user_id`→`users`, `course_id`→`academic_courses`, `validation_code` único, `unique(user_id, course_id)`) y `certificate_history_entries` (FK cascada); `EloquentCertificateRepository` transaccional, borra y reinserta el historial completo en cada `save()` (mismo patrón que `EloquentRoadPassportRepository`).
- **CQRS**: `IssueCertificateCommand`/`RevokeCertificateCommand`/`GetCertificateQuery`/`GetMyCertificatesQuery` con sus handlers. `IssueCertificateHandler` rechaza un segundo certificado (emitido o revocado) para el mismo usuario+curso (`CertificateAlreadyExists`, 409). `GetCertificateHandler` con el mismo patrón de autorización que `GetRoadPassportHandler` (dueño o `certifications.view`). `GetMyCertificatesHandler` lista todos los certificados del usuario (a diferencia del pasaporte, un usuario puede tener varios).
- **Autorización**: permisos nuevos `certifications.manage`/`certifications.view` en `Modules\Authorization\Domain\Enums\Permission`, mismo patrón de concesión que `road_passports.*`: `SuperAdmin` e `InstitutionalAdmin` ambos; `Teacher` solo view; `Student` ninguno (accede a los propios por pertenencia vía `/me`).
- **API HTTP** bajo `auth:sanctum`, prefijo `api/v1/certification`: `POST /certificates` (`certifications.manage`), `GET /certificates/me`, `GET /certificates/{certificateId}` (dueño o `certifications.view`), `POST /certificates/{certificateId}/revoke` (`certifications.manage`). Errores públicos: `CERTIFICATE_NOT_FOUND` (404), `CERTIFICATE_ALREADY_EXISTS` (409), `INVALID_CERTIFICATE_TRANSITION` (422).
- **Pruebas**: 42 tests en total repartidos en dominio (agregado, `ValidationCode`), aplicación (handlers con repositorio en memoria), integración (repositorio Eloquent, service provider) y feature (API HTTP completa, incluyendo autorización por rol y transiciones inválidas).

### Validaciones

- Suite completa del módulo ✅ — `42 passed (93 assertions)`.
- Suite de `RolePermissionsTest` (Authorization) ✅ — `20 passed (80 assertions)` tras agregar los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=certification` ✅ — 5 rutas registradas (status + 4 de certificados).

**Estado:** Finalizado.

## 2026-08-26 — IMP-044 (Cierre de ENG-044 — Consulta pública controlada)

### Alcance acordado con el usuario

Endpoint público (sin autenticación) para verificar un certificado por código de validación, exponiendo el nombre del titular además del mínimo (curso, vigencia efectiva, fechas) — útil para que un verificador externo confirme a quién pertenece el certificado. Vigencia efectiva calculada explícitamente (`valid`/`expired`/`revoked`), no el `status` interno crudo. Diferido explícitamente: listado/enumeración pública de certificados (solo consulta puntual por código exacto), límite de tasa/anti-abuso (preocupación de infraestructura/gateway) y exposición de evidencia cruzada del Pasaporte Vial. Detalle en `docs/plans/2026-08-26-consulta-publica-eng044-design.md`.

### Completado

- **Dominio**: `CertificateEffectiveStatus` (enum `Valid`/`Expired`/`Revoked`) y `Certificate::effectiveStatus(DateTimeImmutable $now): CertificateEffectiveStatus` — método puro en el agregado existente: `Revoked` tiene prioridad sobre la fecha de vigencia; `Expired` si `expiresAt` ya pasó; `Valid` en cualquier otro caso (incluye certificados sin `expiresAt`).
- **Persistencia**: `CertificateRepository::findByValidationCode(ValidationCode): ?Certificate`, implementado en `EloquentCertificateRepository` filtrando por la columna única `validation_code`.
- **CQRS**: `VerifyCertificateQuery(validationCode)` → `VerifyCertificateHandler`. Un código con formato inválido (`ValidationCode::fromString` lanza `InvalidArgumentException`) o inexistente responde igual: `CertificateNotFound::withValidationCode()` (404, mismo código público `CERTIFICATE_NOT_FOUND`) — sin distinguir el motivo. El handler depende directamente de `Modules\Identity\Domain\Repositories\UserRepository` y `Modules\Academic\Domain\Repositories\CourseRepository` para resolver el nombre del titular y del curso — mismo precedente que `AssignRoleHandler` en `Authorization` (que depende de `UserRepository` de `Identity`); no se creó una interfaz de resolución nueva porque no es un enriquecimiento opcional/reactivo, sino un dato siempre requerido por la verificación.
- **`CertificateVerificationResponse`** (DTO público): `validation_code`, `status` (efectivo), `issued_at`, `expires_at`, `course_id`, `course_name`, `holder_name` — sin `user_id`, correo, historial ni el `id` interno del certificado.
- **API HTTP**: `GET /api/v1/certification/verify/{validationCode}` público, sin `auth:sanctum` ni permiso, con restricción de formato a nivel de ruta (`^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$`).
- **Pruebas**: 12 tests nuevos repartidos en dominio (4 casos de `effectiveStatus`), persistencia (`findByValidationCode`), aplicación (6 casos con repositorios en memoria para `Certificate`, `User` y `Course`) y feature (6 casos de API HTTP pública, incluyendo normalización de mayúsculas/minúsculas y ambos tipos de 404).

### Validaciones

- Suite completa del módulo ✅ — `59 passed (129 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=certification` ✅ — 6 rutas registradas (las 5 previas + `verify`).

**Estado:** Finalizado.

## 2026-08-26 — IMP-045 (Cierre de ENG-045 — Registro de simuladores)

### Alcance acordado con el usuario

Primera historia de la Fase 9 (Integración con SIMUDRIVE): registro administrativo de simuladores autorizados. Llaves de integración generadas al registrar/rotar, devueltas **una única vez** en la respuesta HTTP; en base de datos solo se guarda su hash SHA-256 (mismo espíritu que los *personal access tokens* de Sanctum) — si se pierde, solo se puede rotar, no recuperar. Ciclo de vida `Active` → `Suspended` (reversible) → `Active` de nuevo, o `Retired` (terminal, mismo criterio que `RoadPassport::revoke()`/`Certificate::revoke()`). Diferido explícitamente: validación de sesiones/telemetría contra el simulador (ENG-046/047 — este incremento solo registra, no usa el simulador todavía), actualización de versión de software por heartbeat del dispositivo, geolocalización estructurada (`Ubicación` es texto libre). Detalle en `docs/plans/2026-08-26-registro-simuladores-eng045-design.md`.

### Completado

- **Módulo nuevo `Modules\Simulation`** (`modules/Simulation`), siguiendo ENG-003 al pie de la letra: capas Domain/Application/Infrastructure/Presentation, `SimulationServiceProvider` registrado en `bootstrap/providers.php`, endpoint de estado público `GET /api/v1/simulation/status`.
- **Dominio**: `SimulatorId` (VO UUID); `DeviceIdentifier` (VO, no vacío, máximo 100 caracteres, mismo espíritu que `CourseCode`); `IntegrationKey` (VO) — `generate()` crea un valor aleatorio de 32 bytes (`random_bytes`, hexadecimal) y su hash SHA-256; `plainValue()` no nulo solo justo después de `generate()`; `fromHash()` reconstruye desde persistencia; `matches()` compara con `hash_equals()` (seguro contra *timing attacks*); `SimulatorStatus` (enum `Active`/`Suspended`/`Retired`); `SimulatorHistoryEntry` (VO de cambios de estado, mismo patrón que `CertificateHistoryEntry`); agregado `Simulator` (`register()`, `restore()`, `suspend()`, `reactivate()`, `retire()` — transiciones válidas mismo patrón que `RoadPassport`, `retire()` es terminal —, `rotateIntegrationKey()` sin entrada de historial porque no es un cambio de estado).
- **Persistencia**: tablas `simulators` (PK UUID, `device_identifier` único, `integration_key_hash` único) y `simulator_history_entries` (FK cascada); `EloquentSimulatorRepository` transaccional, borra y reinserta el historial completo en cada `save()`.
- **CQRS**: `RegisterSimulatorCommand`/`SuspendSimulatorCommand`/`ReactivateSimulatorCommand`/`RetireSimulatorCommand`/`RotateSimulatorIntegrationKeyCommand`/`GetSimulatorQuery`/`ListSimulatorsQuery` con sus handlers. `RegisterSimulatorHandler` rechaza un `deviceIdentifier` duplicado (`SimulatorAlreadyExists`, 409). `SimulatorResponse` nunca incluye el hash de la llave; un campo opcional `integration_key` (valor plano) solo se agrega en la respuesta de `register`/`rotate-key`.
- **Autorización**: permisos nuevos `simulators.manage`/`simulators.view`, mismo patrón de concesión que `road_passports.*`/`certifications.*`: `SuperAdmin` e `InstitutionalAdmin` ambos; `Teacher` solo view (necesita saber qué simuladores existen para ENG-046); `Student` ninguno.
- **API HTTP** bajo `auth:sanctum`, prefijo `api/v1/simulation`: `POST /simulators` (`simulators.manage`), `GET /simulators` y `GET /simulators/{simulatorId}` (`simulators.view`), `POST /simulators/{simulatorId}/suspend|reactivate|retire|rotate-key` (`simulators.manage`). Errores públicos: `SIMULATOR_NOT_FOUND` (404), `SIMULATOR_ALREADY_EXISTS` (409), `INVALID_SIMULATOR_TRANSITION` (422).
- **Pruebas**: 51 tests en total repartidos en dominio (agregado, `DeviceIdentifier`, `IntegrationKey`), aplicación (handlers con repositorio en memoria), integración (repositorio Eloquent, service provider) y feature (API HTTP completa, incluyendo autorización por rol y transiciones inválidas).

### Validaciones

- Suite completa del módulo ✅ — `51 passed (132 assertions)`.
- Suite de `RolePermissionsTest` (Authorization) ✅ — `22 passed (88 assertions)` tras agregar los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation` ✅ — 8 rutas registradas.

**Estado:** Finalizado.

## 2026-08-26 — IMP-046 (Cierre de ENG-046 — Sesiones de simulación)

### Alcance acordado con el usuario

Ciclo de vida `Scheduled` → `InProgress` (inicio real) → `Completed` (fin real, duración efectiva) o `Cancelled` (solo desde `Scheduled`) — da a ENG-047 (Telemetría) un punto explícito de "sesión activa ahora mismo". Programación en **autoservicio**: cualquier usuario autenticado programa su propia sesión (el `userId` se toma del usuario autenticado, nunca del cuerpo de la petición); administradores/docentes gestionan sesiones de terceros vía `simulation_sessions.manage`/`simulation_sessions.view`, extendiendo el mismo criterio de propiedad ya usado en `GetCertificateHandler`/`GetRoadPassportHandler` también a las transiciones de estado, no solo a la consulta. `Vehículo`/`Escenario` como texto libre (el catálogo real vive en SIMUDRIVE). Diferido explícitamente: detección de conflictos de horario entre sesiones del mismo simulador, re-validación del estado del simulador al iniciar (solo se valida al programar), integración real con telemetría (ENG-047) y resultados prácticos (ENG-048). Detalle en `docs/plans/2026-08-26-sesiones-simulacion-eng046-design.md`.

### Completado

- **Dominio**: segundo agregado independiente en `Modules\Simulation`, `SimulationSession` (`id`, `userId`, `simulatorId`, `vehicleType`, `scenario`, `scheduledAt`, `plannedDurationMinutes`, `status`, `startedAt`/`endedAt` nullable, `history`). `SimulationSessionStatus` (enum `Scheduled`/`InProgress`/`Completed`/`Cancelled`); `SimulationSessionHistoryEntry` (mismo patrón que `SimulatorHistoryEntry`); `schedule()`/`restore()`/`start()`/`complete()`/`cancel()` con las transiciones válidas ya descritas; `actualDurationMinutes(): ?int` calcula minutos entre `startedAt` y `endedAt`, `null` si no está `Completed`.
- **Persistencia**: tablas `simulation_sessions` (FK a `users` y `simulators`) y `simulation_session_history_entries` (FK cascada); `EloquentSimulationSessionRepository` transaccional, mismo patrón de borrar-y-reinsertar historial.
- **CQRS**: `ScheduleSimulationSessionCommand`/`StartSimulationSessionCommand`/`CompleteSimulationSessionCommand`/`CancelSimulationSessionCommand`/`GetSimulationSessionQuery`/`GetMySimulationSessionsQuery`/`ListSimulationSessionsQuery` con sus handlers. `ScheduleSimulationSessionHandler` valida que el simulador exista (`SimulatorNotFound`, reutilizado de ENG-045) y esté `Active` (`SimulatorNotAvailable`, 422, nueva excepción). Los handlers de mutación (`Start`/`Complete`/`Cancel`) y de consulta reciben `userId`+`canManageOthers`/`canViewOthers` y lanzan `SimulationSessionNotFound` (404) tanto si no existe como si no es del usuario y no tiene permiso ampliado — primera vez que este criterio de propiedad se aplica a mutaciones en esta sesión de trabajo, no solo a lecturas.
- **Autorización**: permisos nuevos `simulation_sessions.manage`/`simulation_sessions.view`, mismo patrón de concesión que `road_passports.*`/`certifications.*`/`simulators.*`. Programar una sesión nueva no requiere ningún permiso.
- **API HTTP** bajo `auth:sanctum`, prefijo `api/v1/simulation`: `POST /sessions` (autoservicio), `GET /sessions/me` (autoservicio), `GET /sessions` (`simulation_sessions.view`), `GET /sessions/{sessionId}` (dueño o `simulation_sessions.view`), `POST /sessions/{sessionId}/start|complete|cancel` (dueño o `simulation_sessions.manage`). Errores públicos: `SIMULATION_SESSION_NOT_FOUND` (404), `SIMULATOR_NOT_AVAILABLE` (422), `INVALID_SIMULATION_SESSION_TRANSITION` (422).
- **Pruebas**: 42 tests nuevos repartidos en dominio (agregado), persistencia (repositorio Eloquent, service provider), aplicación (handlers con repositorios en memoria, incluyendo el criterio de propiedad en mutaciones) y feature (API HTTP completa, incluyendo autoservicio, permisos ampliados y transiciones inválidas).

### Validaciones

- Suite completa del módulo ✅ — `93 passed (240 assertions)`.
- Suite de `RolePermissionsTest` (Authorization) ✅ — `24 passed (96 assertions)` tras agregar los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation` ✅ — 15 rutas registradas (las 8 previas + 7 de sesiones).

**Estado:** Finalizado.

## 2026-08-26 — IMP-047 (Cierre de ENG-047 — Telemetría)

### Alcance acordado con el usuario

El simulador mismo reporta la telemetría, autenticado con su llave de integración (ENG-045) — primer mecanismo de autenticación máquina-a-máquina de este backend, no una sesión de usuario Sanctum. Velocidad/frenado/aceleración/dirección (`TelemetrySample`, lectura continua) y colisiones/infracciones/uso de señales/eventos críticos (`TelemetryEvent`, ocurrencia puntual) son dos conceptos separados, sin invariantes de agregado — bitácora de solo-append. Envío por lotes (el simulador buferea y sube periódicamente), no una llamada por lectura. Diferido explícitamente: procesamiento/agregación de la telemetría (ENG-048), límites de tamaño de lote o *rate limiting* (infraestructura), reintentos/idempotencia ante lotes duplicados. Detalle en `docs/plans/2026-08-26-telemetria-eng047-design.md`.

### Completado

- **Dominio**: `TelemetryEventType` (enum `Collision`/`Infraction`/`SignalUsage`/`Critical`); `TelemetrySample` (entidad inmutable: `speedKph` ≥ 0, `brakingPercentage` 0-100, `accelerationMps2` y `steeringAngleDegrees` con signo, `recordedAt`); `TelemetryEvent` (entidad inmutable: `type`, `details` opcional, `occurredAt`). Sin excepciones de dominio nuevas — errores de forma son `InvalidArgumentException`, mismo criterio que `DeviceIdentifier`/`ValidationCode`; la validación primaria vive en `SubmitTelemetryRequest`.
- **Persistencia**: tablas `telemetry_samples`/`telemetry_events` (FK cascada a `simulation_sessions`, sin tabla de historial — *append-only*, no aplica el patrón borrar-y-reinsertar de los agregados). `TelemetrySampleRepository`/`TelemetryEventRepository::saveBatch()` usan `insert()` de Eloquent para inserción masiva, no N `create()` individuales. Nuevo método `SimulatorRepository::findByIntegrationKeyHash()` (mismo patrón que Sanctum: búsqueda directa por hash indexado, sin `hash_equals` en la consulta).
- **Autenticación de simuladores**: `AuthenticateSimulator` (middleware, alias `simulator.auth`, registrado en `bootstrap/app.php` igual que `permission` → `EnsurePermission`) extrae el *bearer token*, calcula su hash SHA-256 y busca el simulador; responde 401 si falta el token, no hay coincidencia, o el simulador no está `Active` — revocar acceso es tan simple como suspender/retirar el simulador (ENG-045). Deja el id del simulador en `$request->attributes` para el controlador.
- **CQRS**: `SubmitTelemetryCommand`/`SubmitTelemetryHandler` valida que la sesión exista y pertenezca al simulador autenticado (`SimulationSessionNotFound`, 404, reutilizado de ENG-046 — aquí por pertenencia al simulador, no al usuario) y que esté `InProgress` (`SimulationSessionNotInProgress`, 422, nueva excepción); construye las entidades y las guarda en lote, devolviendo `TelemetryBatchResponse` (`samples_recorded`, `events_recorded`). `GetSessionTelemetryQuery`/`GetSessionTelemetryHandler` reutiliza el mismo criterio de propiedad que `GetSimulationSessionHandler` (dueño o `simulation_sessions.view`, sin permiso nuevo).
- **API HTTP**: `POST /api/v1/simulation/sessions/{sessionId}/telemetry` con middleware `simulator.auth` (sin `auth:sanctum`), body `samples`/`events` opcionales validados por forma (`SubmitTelemetryRequest`); `GET /api/v1/simulation/sessions/{sessionId}/telemetry` bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view`.
- **Pruebas**: 25 tests nuevos repartidos en dominio (`TelemetrySample`/`TelemetryEvent`), persistencia (repositorios Eloquent, `findByIntegrationKeyHash`), presentación (middleware `AuthenticateSimulator` en aislamiento), aplicación (handlers con repositorios en memoria) y feature (API HTTP completa, incluyendo autenticación de simulador y ambos criterios de pertenencia).

### Validaciones

- Suite completa del módulo ✅ — `124 passed (301 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation` ✅ — 17 rutas registradas (las 15 previas + 2 de telemetría).

**Estado:** Finalizado.

## 2026-08-26 — IMP-048 (Cierre de ENG-048 — Resultados prácticos)

### Alcance acordado con el usuario

Cálculo automático desde la telemetría (sin intervención humana): un servicio de dominio puro cuenta los `TelemetryEvent` ya registrados de una sesión `Completed` y deriva puntaje y resultado general. Sin persistencia nueva — se recalcula en cada consulta a partir de telemetría inmutable, mismo espíritu que el `trust_score` de ENG-042. "Competencias demostradas" es texto libre derivado del escenario (sin depender de `Competency` de Academic). "Evidencias asociadas" son los propios errores del resultado (referencian el `TelemetryEvent` concreto), sin integración con el Pasaporte Vial. Diferido explícitamente: registro manual por un evaluador humano, integración con `RoadPassport::recordEvidence()`, referencias reales a `Competency` de Academic. Detalle en `docs/plans/2026-08-26-resultados-practicos-eng048-design.md`.

### Completado

- **Dominio**: `PracticalResultOutcome` (enum `Passed`/`Failed`); `PracticalResultError` (VO: `type`, `occurredAt`, `penaltyPoints`, `details` — es la evidencia del error); `PracticalResult` (VO: `sessionId`, `outcome`, `score`, `totalPenaltyPoints`, `errors`, `competenciesDemonstrated`, `recommendations`). `PracticalResultCalculator` (servicio de dominio puro, mismo espíritu que `RoadPassportTrustCalculator`/`ExamAttemptGrader`): penaliza colisión -30, infracción -10, evento crítico -20 (`SignalUsage` no penaliza), puntaje con piso en 0, aprueba con ≥ 70; competencia demostrada solo si `Passed`; una recomendación fija por cada tipo de error presente, sin duplicados (`match` exhaustivo sobre `TelemetryEventType`).
- **CQRS**: `GetPracticalResultQuery`/`GetPracticalResultHandler`, mismo criterio de propiedad que `GetSimulationSessionHandler`/`GetSessionTelemetryHandler` (dueño o `simulation_sessions.view`, sin permiso nuevo). Exige `status = Completed` (`PracticalResultNotAvailable`, 422, nueva excepción, si se consulta antes); carga la sesión y su telemetría, invoca el calculador y devuelve `PracticalResultResponse`.
- **API HTTP**: `GET /api/v1/simulation/sessions/{sessionId}/result` bajo `auth:sanctum`, agregado como método `result()` en el `SimulationSessionController` existente (no se creó un controlador nuevo, dado que es una sola consulta de solo lectura).
- **Pruebas**: 16 tests nuevos repartidos en dominio (`PracticalResultCalculator`, 6 casos incluyendo piso en 0 y deduplicación de recomendaciones), aplicación (handler con repositorios en memoria) y feature (API HTTP completa, incluyendo el error 422 de sesión no finalizada).

### Validaciones

- Suite completa del módulo ✅ — `141 passed (345 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation` ✅ — 18 rutas registradas (las 17 previas + `result`).

**Estado:** Finalizado.

## 2026-08-26 — IMP-049 (Cierre de ENG-049 — SIMUDRIVE Decision Engine)

### Alcance acordado con el usuario

El simulador reporta datos crudos por punto de decisión (contexto vial en texto libre, nivel de riesgo asignado por el diseño del escenario en SIMUDRIVE, reacción del conductor de un conjunto cerrado necesario para evaluación determinística) y un servicio de dominio en EDUDRIVE evalúa si la reacción fue apropiada, genera retroalimentación y calcula consistencia — EDUDRIVE decide, no solo ingiere. Consistencia con alcance limitado a la sesión actual (se agrupa por nivel de riesgo, no se compara contra el historial completo del usuario). Envío por lotes, igual que la telemetría. Sin persistencia del resultado evaluado — se calcula en cada consulta a partir de los puntos de decisión crudos ya persistidos, mismo patrón que ENG-048. Diferido explícitamente: consistencia entre sesiones o de todo el historial del usuario, que SIMUDRIVE reporte la evaluación ya calculada, retroalimentación personalizada más allá de mensajes fijos. Detalle en `docs/plans/2026-08-26-decision-engine-eng049-design.md`.

### Completado

- **Dominio**: `DecisionRiskLevel` (enum `Low`/`Medium`/`High`); `DriverReactionType` (enum `Braked`/`Accelerated`/`Maintained`/`Swerved`/`Signaled`/`Ignored` — conjunto cerrado, no texto libre, precisamente para permitir la evaluación determinística); `DecisionEvaluationOutcome` (enum `Appropriate`/`Inappropriate`); `DecisionPoint` (entidad inmutable, solo-append, mismo espíritu que `TelemetryEvent`); `DecisionPointEvaluation`/`DecisionEngineResult` (VOs del resultado evaluado). `DecisionEngineCalculator` (servicio de dominio puro, mismo espíritu que `PracticalResultCalculator`): tabla de reacciones apropiadas por nivel de riesgo (`ignored` nunca apropiado; `high` solo `braked`/`swerved`/`signaled`; `medium` suma `maintained`; `low` cualquiera salvo `ignored`); retroalimentación fija por combinación riesgo+resultado (`match` exhaustivo); consistencia agrupando evaluaciones por `riskLevel` — un grupo es consistente si todas sus reacciones comparten el mismo resultado, `consistency_score = grupos_consistentes / grupos_totales` (1.0 si no hay puntos de decisión).
- **Persistencia**: tabla `decision_points` (FK cascada a `simulation_sessions`, sin tabla de historial — *append-only*, igual que `telemetry_events`). `DecisionPointRepository::saveBatch()`/`allForSession()`.
- **CQRS**: `SubmitDecisionPointsCommand`/`SubmitDecisionPointsHandler`, mismo patrón de validación que `SubmitTelemetryHandler` (sesión existe, pertenece al simulador autenticado, está `InProgress`). `GetDecisionEngineResultQuery`/`GetDecisionEngineResultHandler`, mismo criterio de propiedad que `GetPracticalResultHandler` (dueño o `simulation_sessions.view`, sin permiso nuevo); exige `status = Completed` (`DecisionEngineResultNotAvailable`, 422, nueva excepción).
- **API HTTP**: `POST /api/v1/simulation/sessions/{sessionId}/decisions` con middleware `simulator.auth` (sin `auth:sanctum`), body `decisions` validado por forma (`SubmitDecisionPointsRequest`); `GET /api/v1/simulation/sessions/{sessionId}/decisions` bajo `auth:sanctum`, dueño de la sesión o `simulation_sessions.view`.
- **Pruebas**: 26 tests nuevos repartidos en dominio (`DecisionEngineCalculator`, 9 casos incluyendo reglas por nivel de riesgo y consistencia parcial/total), persistencia (repositorio Eloquent), aplicación (handlers con repositorios en memoria) y feature (API HTTP completa, incluyendo autenticación de simulador y ambos criterios de pertenencia).

### Validaciones

- Suite completa del módulo ✅ — `165 passed (394 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation` ✅ — 20 rutas registradas (las 18 previas + 2 de decisiones).

**Estado:** Finalizado.

## 2026-08-26 — IMP-050 (Cierre de ENG-050 — Sincronización offline)

### Alcance acordado con el usuario

La cola local y el manejo de la desconexión son responsabilidad del simulador (fuera de alcance de este backend, mismo criterio que el catálogo real de vehículos/escenarios en ENG-046). El trabajo de EDUDRIVE es que los endpoints de telemetría (ENG-047) y decisiones (ENG-049) ya construidos acepten reenvíos sin duplicar datos y toleren que lleguen tarde. Identificadores idempotentes por ítem (no una llave de idempotencia por lote completo, evita una tabla nueva). Datos tardíos: se aceptan si ocurrieron durante el periodo real en que la sesión estuvo en curso (comparando marca de tiempo contra `startedAt`/`endedAt`), sin importar el estado actual de la sesión. Diferido explícitamente: modelar la sesión offline como concepto de dominio propio (sesión completa reportada retroactivamente en un solo envío), tabla de llaves de idempotencia por lote, resolución de conflictos más allá de la ventana temporal. Detalle en `docs/plans/2026-08-26-sincronizacion-offline-eng050-design.md`.

### Completado

- **Dominio**: `SimulationSession::wasInProgressAt(DateTimeImmutable): bool` — método de consulta puro nuevo: `false` si `startedAt` es nulo (cubre `Scheduled` y `Cancelled`, ya que `cancel()` solo es posible desde `Scheduled`) o si la marca de tiempo es anterior a `startedAt`; `false` si `endedAt` no es nulo y la marca de tiempo es posterior; `true` en cualquier otro caso (incluye `InProgress` completo y `Completed` dentro de su ventana real).
- **Persistencia**: `TelemetrySampleRepository`/`TelemetryEventRepository`/`DecisionPointRepository::saveBatch()` cambian de `void` a `int` (filas realmente insertadas); las implementaciones Eloquent usan `insertOrIgnore()` en vez de `insert()` — un `id` ya existente en base de datos se omite silenciosamente.
- **CQRS**: `SubmitTelemetryCommand`/`SubmitDecisionPointsCommand` ahora incluyen `id` por ítem (provisto por el simulador, no generado con `Str::uuid()` al guardar). `SubmitTelemetryHandler`/`SubmitDecisionPointsHandler` cambian la validación de "sesión `InProgress` en este momento" a dos pasos: rechazar de entrada si la sesión nunca se inició (`startedAt` nulo, preserva el rechazo de una sesión `Scheduled`/`Cancelled` incluso con un lote vacío), y luego exigir que **todos** los ítems del lote satisfagan `wasInProgressAt()` contra su propia marca de tiempo — si alguno cae fuera de la ventana, se rechaza el lote completo (mismo código `SIMULATION_SESSION_NOT_IN_PROGRESS` ya existente, criterio ampliado). El conteo de la respuesta viene del valor real que devuelve `saveBatch()`.
- **API HTTP**: `SubmitTelemetryRequest`/`SubmitDecisionPointsRequest` agregan `required|uuid` para `samples.*.id`/`events.*.id`/`decisions.*.id`. Sin cambios en rutas ni permisos.
- **Pruebas**: 20 tests nuevos/actualizados repartidos en dominio (`wasInProgressAt`, 4 casos), persistencia (idempotencia por repositorio), aplicación (idempotencia y ventana temporal por handler) y feature (idempotencia y datos tardíos end-to-end vía HTTP).

### Validaciones

- Suite completa del módulo ✅ — `181 passed (437 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- Sin cambios en rutas registradas (20, mismas de ENG-049).

**Estado:** Finalizado. Con esto cierra por completo la **Fase 9 — Integración con SIMUDRIVE** (ENG-045 a ENG-050).

## 2026-08-26 — IMP-051 (Cierre de ENG-051 — Logros)

### Alcance acordado con el usuario

Primera historia de la **Fase 10 — Gamificación**. Otorgamiento manual de logros vía `achievements.manage`, en vez de evaluación automática de reglas — la "regla de obtención" queda como texto libre descriptivo, sin motor de evaluación (mismo criterio que la emisión manual de `Certificate` en ENG-043). Ciclo de vida del catálogo limitado a `active`/`retired` sin reversión, sin lista de historial dedicada (a diferencia de `Simulator`/`RoadPassport`/`Certificate`) porque solo existe una transición posible. `achievements.view` se extiende también a `Student` — desviación deliberada del criterio usado en `road_passports`/`certifications`/`simulators` (donde solo administración e instructor pueden ver), porque el catálogo de logros es de navegación abierta y motivacional, equivalente a `courses.view`, no un registro personal. Diferido explícitamente: revocación de un logro ya otorgado, consulta de los logros obtenidos por otro usuario (solo autoservicio vía `/achievements/me`), evaluación automática de reglas de obtención. Detalle en `docs/plans/2026-08-26-logros-eng051-design.md`.

### Completado

- **Dominio**: nuevo módulo `Modules\Gamification`. `AchievementId` (VO UUID, mismo patrón que `SimulatorId`); `AchievementCode` (VO, mismo patrón que `CourseCode`: máximo 50 caracteres, normalizado a mayúsculas, regex `^[A-Z0-9]+(?:-[A-Z0-9]+)*$`); `AchievementStatus` (enum `Active`/`Retired`). Agregado `Achievement` (constructor privado, `create()`/`restore()`, método `retire(?string $reason, DateTimeImmutable $at)` que lanza `InvalidAchievementTransition` si ya está retirado). Campo `registeredAt` (no `createdAt`) para evitar colisión con las columnas de auditoría automáticas de Eloquent, mismo criterio que `Simulator::registeredAt()` (ENG-045). Entidad `UserAchievement` (otorgamiento inmutable de solo-append, mismo espíritu que `TelemetryEvent`/`DecisionPoint`: `grant()` estático, sin métodos de ciclo de vida).
- **Persistencia**: tablas `achievements` (código único) y `user_achievements` (FK cascada a `achievements` y a `users`, único por `achievement_id`+`user_id`). `AchievementRepository`/`UserAchievementRepository` con sus implementaciones Eloquent (`updateOrCreate`).
- **CQRS**: `CreateAchievementCommand`/`RetireAchievementCommand`/`GrantAchievementCommand` y `GetAchievementQuery`/`ListAchievementsQuery`/`GetMyAchievementsQuery`, con sus handlers. `GrantAchievementHandler` exige que el logro esté `active` (`AchievementNotAvailable`, 422) y rechaza un otorgamiento duplicado por usuario+logro (`AchievementAlreadyGranted`, 409). Excepciones uniformes `AchievementNotFound` (404) y `AchievementAlreadyExists` (409, código duplicado).
- **Autorización**: nuevos permisos `achievements.manage`/`achievements.view` en `Modules\Authorization`. `achievements.manage`: SuperAdmin e InstitutionalAdmin. `achievements.view`: SuperAdmin, InstitutionalAdmin, Teacher y **Student** (primera vez que Student recibe un permiso de una historia de Fase 8/9/10).
- **API HTTP**: `/api/v1/gamification/achievements` — `POST`/`POST {id}/retire`/`POST {id}/grant` bajo `achievements.manage`; `GET`/`GET {id}` bajo `achievements.view`; `GET /achievements/me` bajo `auth:sanctum` sin permiso adicional (autoservicio).
- **Pruebas**: 44 tests repartidos en dominio (`Achievement`, `UserAchievement`, `AchievementCode`), persistencia (ambos repositorios Eloquent), aplicación (handlers con repositorios en memoria), proveedor de servicios y feature (API HTTP completa, incluyendo el criterio `achievements.view` extendido a Student y el autoservicio de `/achievements/me`).

### Validaciones

- Suite completa del módulo ✅ — `44 passed (96 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos de esta historia.
- `php artisan route:list --path=gamification` ✅ — 7 rutas registradas.

**Estado:** Finalizado.

## 2026-08-26 — IMP-052 (Cierre de ENG-052 — Insignias)

### Alcance acordado con el usuario

Segunda historia de la Fase 10 — Gamificación, extiende `Modules\Gamification` (creado en ENG-051) con un segundo agregado, `Badge`. Otorgamiento manual vía `badges.manage`, sin motor de evaluación automática de reglas — mismo criterio que `Achievement`/`Certificate`. "Niveles" se modela como un atributo fijo de la insignia (`BadgeLevel`: bronce/plata/oro), sin sistema de progresión ni acumulación — eso corresponde a ENG-053 (Experiencia y niveles), un concepto distinto (nivel del usuario, no de la insignia). "Versionado" se modela como un campo `version` (entero) que se incrementa al editar el contenido; el otorgamiento guarda la versión vigente en ese momento, pero no se conservan snapshots históricos completos del contenido anterior (a diferencia de `CourseVersion` en Academic). Categoría modelada como enum cerrado `BadgeCategory` (educativa/institucional/práctica), tal como las enumera el roadmap. Diferido explícitamente: evaluación automática de reglas, sistema de progresión de niveles, historial completo de snapshots por versión, revocación de una insignia otorgada, consulta de las insignias de otro usuario. Detalle en `docs/plans/2026-08-26-insignias-eng052-design.md`.

### Completado

- **Dominio**: `BadgeId`/`BadgeCode` (mismos patrones que `AchievementId`/`AchievementCode`); `BadgeCategory` (`Educational`/`Institutional`/`Practical`); `BadgeLevel` (`Bronze`/`Silver`/`Gold`); `BadgeStatus` (`Active`/`Retired`). Agregado `Badge` (constructor privado, `create()`/`restore()`, `updateContent()` que reemplaza nombre/descripción/criterio/categoría/nivel e incrementa `version`, `retire()` mismo criterio que `Achievement::retire()`). `InvalidBadgeTransition` (422) con dos factorías: `alreadyRetired()` (reutilizada de "retirar dos veces") y `cannotEditRetired()` (edición bloqueada si `status === Retired`). Entidad `UserBadge` (otorgamiento inmutable de solo-append, con `awardedVersion` denormalizado desde `Badge::version()` al momento del otorgamiento).
- **Persistencia**: tablas `badges` (código único, `category`/`level`/`version`/`status` como columnas propias) y `user_badges` (FK cascada a `badges` y a `users`, único por `badge_id`+`user_id`, columna `awarded_version`). `BadgeRepository`/`UserBadgeRepository` con sus implementaciones Eloquent (`updateOrCreate`).
- **CQRS**: `CreateBadgeCommand`/`UpdateBadgeCommand`/`RetireBadgeCommand`/`GrantBadgeCommand` y `GetBadgeQuery`/`ListBadgesQuery`/`GetMyBadgesQuery`, con sus handlers — `UpdateBadgeHandler` es el único nuevo respecto al patrón de `Achievement` (ENG-051 no tenía edición de contenido). `GrantBadgeHandler` exige que la insignia esté `active` (`BadgeNotAvailable`, 422) y rechaza un otorgamiento duplicado por usuario+insignia (`BadgeAlreadyGranted`, 409); denormaliza `awardedVersion` desde `Badge::version()`. Excepciones uniformes `BadgeNotFound` (404) y `BadgeAlreadyExists` (409).
- **Autorización**: nuevos permisos `badges.manage`/`badges.view` (independientes de `achievements.*`, catálogos separados). `badges.manage`: SuperAdmin e InstitutionalAdmin. `badges.view`: SuperAdmin, InstitutionalAdmin, Teacher y **Student** (mismo criterio que `achievements.view`).
- **API HTTP**: `/api/v1/gamification/badges` — `POST`/`PUT {id}`/`POST {id}/retire`/`POST {id}/grant` bajo `badges.manage`; `GET`/`GET {id}` bajo `badges.view`; `GET /badges/me` bajo `auth:sanctum` sin permiso adicional (autoservicio). `PUT /badges/{badgeId}` para la edición de contenido, mismo verbo que `QuestionController::update`/`ExamController::update` en Academic.
- **Pruebas**: 51 tests nuevos repartidos en dominio (`Badge`, `UserBadge`, `BadgeCode`), persistencia (ambos repositorios Eloquent, incluyendo el incremento de versión), aplicación (handlers con repositorios en memoria, incluyendo `UpdateBadgeHandler` y el bloqueo de edición sobre una insignia retirada), proveedor de servicios y feature (API HTTP completa, incluyendo la edición con incremento de versión y el otorgamiento con `awarded_version`).

### Validaciones

- Suite completa del módulo ✅ — `95 passed (224 assertions)`.
- Suite completa de Authorization ✅ — `47 passed (147 assertions)`, confirmando los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=gamification` ✅ — 14 rutas registradas (las 7 previas de ENG-051 + 7 de insignias).

**Estado:** Finalizado.
