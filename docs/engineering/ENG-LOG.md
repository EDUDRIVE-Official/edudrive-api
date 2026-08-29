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

## 2026-08-26 — IMP-053 (Cierre de ENG-053 — Experiencia y niveles)

### Alcance acordado con el usuario

Tercera historia de la Fase 10 — Gamificación, extiende `Modules\Gamification` con un tercer concepto, `ExperienceEntry`, distinto de `Achievement`/`Badge` en que no es un catálogo: es un ledger de solo-append de puntos de experiencia (XP). Otorgamiento manual vía `experience.manage`, sin integración automática reactiva con otros módulos (logros, insignias, cursos, exámenes) como fuente de XP. Nivel general y nivel por competencia calculados mediante un servicio de dominio puro en cada consulta, sin persistirse — mismo patrón que `PracticalResultCalculator`/`DecisionEngineCalculator`/`RoadPassportTrustCalculator`. Regla de progresión con fórmula fija y umbral uniforme (`nivel = floor(xp_total / 100) + 1`), sin tabla de umbrales configurable. Prevención de manipulación: ledger inmutable de solo-append, puntos estrictamente positivos, y sin autoservicio de registro (un estudiante nunca puede otorgarse XP a sí mismo). Diferido explícitamente: integración automática reactiva, tabla de umbrales configurable, consulta del resumen de experiencia de otro usuario, edición/borrado de un registro, referencias reales a `Competency` de Academic. Detalle en `docs/plans/2026-08-26-experiencia-niveles-eng053-design.md`.

### Completado

- **Dominio**: entidad `ExperienceEntry` (inmutable, solo-append, mismo espíritu que `UserAchievement`/`UserBadge`/`TelemetryEvent`): `id`, `userId`, `points` (validado estrictamente positivo en el constructor vía `record()`, `InvalidArgumentException` si no), `competencyId` opcional en texto libre, `reason`, `recordedAt`. VOs `CompetencyExperience` (`competencyId`, `totalPoints`, `level`) y `ExperienceSummary` (`userId`, `totalPoints`, `generalLevel`, `competencies`). Servicio de dominio `ExperienceLevelCalculator::summarize(userId, entries)`: suma todos los puntos del usuario para el nivel general; agrupa por `competencyId` (ignorando registros sin competencia) para el nivel por competencia; ambos usan la misma fórmula `intdiv(puntos, 100) + 1`.
- **Persistencia**: tabla `experience_entries` (FK cascada a `users`, `points` sin signo, `competency_id` nullable, sin restricción de unicidad — un usuario puede tener muchos registros). `ExperienceEntryRepository` con su implementación Eloquent (`updateOrCreate`, `allForUser()`).
- **CQRS**: `RecordExperienceCommand`/`RecordExperienceHandler` (crea y guarda un `ExperienceEntry` nuevo). `GetMyExperienceSummaryQuery`/`GetMyExperienceSummaryHandler` (instancia `ExperienceLevelCalculator` directamente, mismo patrón que `GetPracticalResultHandler` con `PracticalResultCalculator` — sin inyección ni registro en el contenedor).
- **Autorización**: un solo permiso nuevo, `experience.manage` (SuperAdmin + InstitutionalAdmin, mismo criterio que `achievements.manage`/`badges.manage`). Sin `experience.view` — la consulta es autoservicio únicamente.
- **API HTTP**: `POST /api/v1/gamification/experience/grant` bajo `experience.manage`; `GET /api/v1/gamification/experience/me` bajo `auth:sanctum` sin permiso adicional (autoservicio), devuelve `total_points`, `general_level` y el arreglo `competencies`.
- **Pruebas**: 24 tests nuevos repartidos en dominio (`ExperienceEntry`, `ExperienceLevelCalculator` — incluyendo el cálculo independiente de nivel general vs. por competencia), persistencia (repositorio Eloquent), aplicación (handlers con repositorio en memoria), proveedor de servicios y feature (API HTTP completa, incluyendo el resumen con nivel general y por competencia, y el rechazo de puntos no positivos).

### Validaciones

- Suite completa del módulo ✅ — `119 passed (286 assertions)`.
- Suite completa de Authorization ✅ — `48 passed (151 assertions)`, confirmando el permiso nuevo.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=gamification` ✅ — 16 rutas registradas (las 14 previas de ENG-051/052 + 2 de experiencia).

**Estado:** Finalizado.

## 2026-08-26 — IMP-054 (Cierre de ENG-054 — Retos y misiones)

### Alcance acordado con el usuario

Cuarta y última historia de la Fase 10 — Gamificación. Retos individuales, grupales y misiones educativas se modelan con un solo agregado `Challenge` y un enum cerrado `ChallengeType`, sin modelar un concepto nuevo de equipo/grupo con membresía propia — un reto "grupal" es simplemente uno en el que participan varios usuarios, cada uno con su propio registro de participación. Todo el registro (unirse y finalizar) es manual vía `challenges.manage`, mismo criterio que `Achievement`/`Badge`, sin autoservicio de "unirse". Las fechas de vigencia restringen funcionalmente cuándo se puede registrar una participación nueva. La recompensa es texto libre descriptivo, sin vincularse ni otorgar automáticamente un `Achievement`/`Badge` real. Diferido explícitamente: concepto real de equipo/grupo, autoservicio de unión, otorgamiento automático de un logro/insignia real, consulta de las participaciones de otro usuario, reversión de una participación ya completada. Detalle en `docs/plans/2026-08-26-retos-misiones-eng054-design.md`.

### Completado

- **Dominio**: `ChallengeId`/`ChallengeCode` (mismos patrones que `AchievementId`/`AchievementCode`); `ChallengeType` (`Individual`/`Group`/`Educational`); `ChallengeStatus` (`Active`/`Retired`); `ChallengeParticipationStatus` (`Joined`/`Completed`). Agregado `Challenge` (constructor privado, `create()`/`restore()` validan que `endsAt` sea posterior a `startsAt` vía `guardDateWindow()`, `retire()` mismo criterio que `Achievement`/`Badge`, `isWithinWindow(DateTimeImmutable): bool` método de consulta puro). Entidad `ChallengeParticipation` — a diferencia de `UserAchievement`/`UserBadge`, no es un registro de solo-append inmutable: `join()` estático y `complete(?string $evidence, DateTimeImmutable $at)` mutador con guarda de invariante (`InvalidChallengeParticipationTransition`, 422, si ya está `Completed`), mismo espíritu que la transición `Active`→`Retired` de `Badge`.
- **Persistencia**: tabla `challenges` (código único, `type`/`status` como columnas propias, `starts_at`/`ends_at`) y `challenge_participations` (FK cascada a `challenges` y a `users`, único por `challenge_id`+`user_id`). `ChallengeRepository`/`ChallengeParticipationRepository` con sus implementaciones Eloquent (`updateOrCreate`).
- **CQRS**: `CreateChallengeCommand`/`RetireChallengeCommand`/`JoinChallengeCommand`/`CompleteChallengeParticipationCommand` y `GetChallengeQuery`/`ListChallengesQuery`/`GetMyChallengeParticipationsQuery`, con sus handlers. `JoinChallengeHandler` exige que el reto esté `active` **y** dentro de su ventana de vigencia (`ChallengeNotAvailable`, 422) y rechaza una unión duplicada (`ChallengeAlreadyJoined`, 409). `CompleteChallengeParticipationHandler` busca la participación existente por reto+usuario (`ChallengeParticipationNotFound`, 404, si no existe) y llama a `complete()`. Excepciones uniformes `ChallengeNotFound` (404) y `ChallengeAlreadyExists` (409).
- **Autorización**: nuevos permisos `challenges.manage`/`challenges.view` (independientes de `achievements.*`/`badges.*`). `challenges.manage`: SuperAdmin e InstitutionalAdmin. `challenges.view`: SuperAdmin, InstitutionalAdmin, Teacher y **Student** (mismo criterio que `achievements.view`/`badges.view`).
- **API HTTP**: `/api/v1/gamification/challenges` — `POST`/`POST {id}/retire`/`POST {id}/join`/`POST {id}/complete` bajo `challenges.manage`; `GET`/`GET {id}` bajo `challenges.view`; `GET /challenges/me` bajo `auth:sanctum` sin permiso adicional (autoservicio de consulta de participaciones propias).
- **Pruebas**: 55 tests nuevos repartidos en dominio (`Challenge` — incluyendo la validación de ventana de fechas y `isWithinWindow()`, `ChallengeParticipation` — incluyendo la transición `Joined`→`Completed`, `ChallengeCode`), persistencia (ambos repositorios Eloquent), aplicación (handlers con repositorios en memoria, incluyendo el rechazo por ventana de fechas), proveedor de servicios y feature (API HTTP completa, incluyendo unión dentro/fuera de ventana, finalización y autoservicio de `/challenges/me`).

### Validaciones

- Suite completa del módulo ✅ — `174 passed (415 assertions)`.
- Suite completa de Authorization ✅ — `50 passed (159 assertions)`, confirmando los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=gamification` ✅ — 23 rutas registradas (las 16 previas de ENG-051/052/053 + 7 de retos).

**Estado:** Finalizado. Con esto cierra por completo la **Fase 10 — Gamificación** (ENG-051 a ENG-054).

## 2026-08-26 — IMP-056 (Cierre de ENG-056 — Motor de notificaciones)

### Alcance acordado con el usuario

Primera historia de la Fase 11 — Comunicación y notificaciones. Nuevo módulo `Modules\Notification`. Solo registro y seguimiento de la notificación — el canal es un metadato (`email`/`web`/`mobile`/`internal_message`); la entrega real por cada canal externo (SMTP, proveedor push) queda diferida como preocupación de infraestructura, mismo criterio que el catálogo real de vehículos/escenarios diferido en ENG-046. Envío manual vía `notifications.manage`, sin disparo automático desde otros módulos en esta historia. Seguimiento con estado de lectura simple `unread`/`read`, marcado en autoservicio por el propio destinatario. Cada notificación incluye una `category` en texto libre sin catálogo cerrado, pensada para ENG-057 (Preferencias de notificación). Diferido explícitamente: integración real de entrega, disparo automático desde eventos de otros módulos, estado de entrega granular con reintentos, catálogo cerrado de categorías, plantillas de comunicación (ENG-058). Detalle en `docs/plans/2026-08-26-motor-notificaciones-eng056-design.md`.

### Completado

- **Bootstrap**: módulo nuevo `Modules\Notification`, con `NotificationServiceProvider`, endpoint `/api/v1/notification/status` y registro en `bootstrap/providers.php`, mismo patrón de arranque que `Modules\Gamification` (ENG-051).
- **Dominio**: `NotificationId` (mismo patrón que `AchievementId`); `NotificationChannel` (`Email`/`Web`/`Mobile`/`InternalMessage`); `NotificationStatus` (`Unread`/`Read`). Agregado `Notification` — no es un catálogo con grant separado como `Achievement`/`Badge`, es una entidad por notificación individual: `send()` estático (`status = Unread`), `markAsRead(DateTimeImmutable $at)` mutador con guarda de invariante (`InvalidNotificationTransition`, 422, si ya está `Read`).
- **Persistencia**: tabla `notifications` (FK cascada a `users`, `channel`/`category`/`status` como columnas propias, `sent_at`/`read_at`). `NotificationRepository` con su implementación Eloquent (`updateOrCreate`, `findById()`, `allForUser()`).
- **CQRS**: `SendNotificationCommand`/`SendNotificationHandler` (crea y guarda una `Notification` nueva). `MarkNotificationAsReadCommand`/`MarkNotificationAsReadHandler` — busca por id y **exige que `userId` coincida con el solicitante**; si no existe o no pertenece al solicitante, lanza `NotificationNotFound` uniformemente (patrón anti-fuga, mismo criterio que `RoadPassport`/`SimulationSession`). `GetMyNotificationsQuery`/`GetMyNotificationsHandler` (autoservicio, lista las notificaciones del usuario autenticado).
- **Autorización**: un solo permiso nuevo, `notifications.manage` (SuperAdmin + InstitutionalAdmin). Sin `notifications.view` — no hay un catálogo administrable que listar, la consulta es autoservicio únicamente, mismo criterio que `experience.manage`.
- **API HTTP**: `POST /api/v1/notification/notifications` bajo `notifications.manage`; `GET /api/v1/notification/notifications/me` y `POST /api/v1/notification/notifications/{notificationId}/read` bajo `auth:sanctum` sin permiso adicional (autoservicio, con verificación de pertenencia en el segundo).
- **Pruebas**: 23 tests nuevos repartidos en bootstrap (endpoint de estado), dominio (`Notification`, incluyendo la transición `unread`→`read`), persistencia (repositorio Eloquent), aplicación (handlers con repositorio en memoria, incluyendo el rechazo anti-fuga), proveedor de servicios y feature (API HTTP completa, incluyendo el envío, la consulta y el marcado como leída propio vs. de otro usuario).

### Validaciones

- Suite completa del módulo ✅ — `23 passed (54 assertions)`.
- Suite completa de Authorization ✅ — `51 passed (163 assertions)`, confirmando el permiso nuevo.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos de esta historia.
- `php artisan route:list --path=notification` ✅ — 4 rutas registradas.

**Estado:** Finalizado.

## 2026-08-26 — IMP-057 (Cierre de ENG-057 — Preferencias de notificación)

### Alcance acordado con el usuario

Segunda historia de la Fase 11, extiende `Modules\Notification` con `NotificationPreference`. Se aplica activamente: `SendNotificationHandler` consulta la preferencia del destinatario antes de registrar la notificación y la descarta silenciosamente si el canal, la categoría o el consentimiento no lo permiten. Todo permitido por defecto con silenciamiento explícito (`allowedChannels`/`mutedCategories`), en vez de un modelo allowlist más restrictivo. Frecuencia y horario de silencio solo se almacenan como configuración, sin aplicarse — requieren un motor de programación/cola que no existe aún. Consentimiento como booleano simple, otorgado por defecto porque las notificaciones son operativas/educativas, no de marketing. Diferido explícitamente: aplicación real de frecuencia/horario de silencio, catálogo cerrado de categorías, historial de consentimientos versionado, gestión administrativa de las preferencias de otro usuario. Detalle en `docs/plans/2026-08-26-preferencias-notificacion-eng057-design.md`.

### Completado

- **Dominio**: `NotificationFrequency` (`Immediate`/`Daily`/`Weekly`). Agregado `NotificationPreference` — registro de configuración por usuario, no catálogo ni ledger: `default($userId)` (todo permitido, `immediate`, sin horario de silencio, consentimiento otorgado, sin fecha), `restore()`, `update()` (valida que el horario de silencio sea ambos `null` o ambos `HH:MM` válidos vía `guardQuietHours()`, sin restringir el orden porque puede cruzar la medianoche), `giveConsent(DateTimeImmutable $at)`/`revokeConsent(DateTimeImmutable $at)`, y el método de consulta `allows(NotificationChannel $channel, string $category): bool` que combina consentimiento + canal permitido + categoría no silenciada.
- **Persistencia**: tabla `notification_preferences` (clave primaria `user_id`, FK a `users`; `allowed_channels`/`muted_categories` como columnas `json`). `NotificationPreferenceRepository` con su implementación Eloquent (`updateOrCreate`, `findByUserId()`).
- **CQRS**: `UpdateNotificationPreferenceCommand`/`UpdateNotificationPreferenceHandler`, `GiveNotificationConsentCommand`/`GiveNotificationConsentHandler`, `RevokeNotificationConsentCommand`/`RevokeNotificationConsentHandler`, `GetMyNotificationPreferenceQuery`/`GetMyNotificationPreferenceHandler` — todos usan `NotificationPreference::default($userId)` como valor de respaldo cuando el usuario no tiene un registro previo, en vez de exigir inicialización explícita. **Cambio en `SendNotificationHandler` (ENG-056)**: ahora recibe también `NotificationPreferenceRepository`, consulta `preference->allows()` antes de crear la `Notification`, y su firma cambia de `handle(): NotificationResponse` a `handle(): ?NotificationResponse` (`null` = descartada por preferencia, no es un error).
- **API HTTP**: `GET /api/v1/notification/preferences/me`, `PUT /api/v1/notification/preferences/me`, `POST /api/v1/notification/preferences/me/consent`, `DELETE /api/v1/notification/preferences/me/consent` — las cuatro bajo `auth:sanctum` sin permiso nuevo (autoservicio). `NotificationController::store` ajustado: responde `200 OK` con `{"data": null}` cuando el envío se descarta por preferencia, `201 Created` con la notificación cuando se registra.
- **Pruebas**: 27 tests nuevos/actualizados repartidos en dominio (`NotificationPreference`, incluyendo el horario de silencio y las transiciones de consentimiento), persistencia (repositorio Eloquent), aplicación (handlers de preferencia con repositorio en memoria, y `SendNotificationHandler` actualizado con el caso de descarte por canal no permitido) y feature (API HTTP completa, incluyendo la integración real: actualizar la preferencia de un usuario y confirmar que el envío subsecuente se descarta).

### Validaciones

- Suite completa del módulo ✅ — `50 passed (141 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=notification` ✅ — 8 rutas registradas (las 4 previas de ENG-056 + 4 de preferencias).

**Estado:** Finalizado.

## 2026-08-26 — IMP-058 (Cierre de ENG-058 — Plantillas de comunicación)

### Alcance acordado con el usuario

Tercera y última historia de la Fase 11, extiende `Modules\Notification` con `CommunicationTemplate` — un catálogo versionado de plantillas de contenido, capacidad independiente del envío de notificaciones: `SendNotificationCommand` no se modifica para aceptar una plantilla. Idiomas modelados como una fila por código+idioma, cada una con su propio ciclo de versión independiente (código único por idioma, no globalmente único). Marca institucional sin mecanismo nuevo — convención de variables reservadas que el llamador provee al renderizar. Variables declaradas obligatorias al renderizar: falta alguna lanza un error; placeholders no declarados quedan como texto literal. Diferido explícitamente: integración con el envío, plantillas específicas por organización con resolución en cascada, motor de plantillas real, historial completo de versiones anteriores. Detalle en `docs/plans/2026-08-26-plantillas-comunicacion-eng058-design.md`.

### Completado

- **Dominio**: `CommunicationTemplateId`/`CommunicationTemplateCode` (mismos patrones que `AchievementId`/`AchievementCode`); `CommunicationTemplateStatus` (`Active`/`Retired`); VO `RenderedTemplate` (`subject`, `body`). Agregado `CommunicationTemplate` — `create()`/`restore()` validan el formato ISO del idioma vía `guardLocale()` (regex `^[a-z]{2}(-[A-Z]{2})?$`); `updateContent()` incrementa `version`, bloqueado si `retired` (`InvalidCommunicationTemplateTransition`, 422, reutilizada también para "retirar dos veces"); `render(array $values): RenderedTemplate` exige que todas las `variables` declaradas estén presentes en `$values` (`MissingTemplateVariable`, 422, si falta alguna) y sustituye cada `{{nombre}}` por su valor vía `str_replace` en `subjectTemplate`/`bodyTemplate` — un placeholder no declarado en el texto queda intacto.
- **Persistencia**: tabla `communication_templates` (`variables` como columna `json`, único por `code`+`locale`, no por `code` solo). `CommunicationTemplateRepository` con su implementación Eloquent (`findByCodeAndLocale()` en vez de `findByCode()`, `updateOrCreate`).
- **CQRS**: `CreateCommunicationTemplateCommand`/`UpdateCommunicationTemplateCommand`/`RetireCommunicationTemplateCommand` y `GetCommunicationTemplateQuery`/`ListCommunicationTemplatesQuery`/`PreviewCommunicationTemplateQuery`, con sus handlers. `CreateCommunicationTemplateHandler` verifica duplicados por código+idioma (`CommunicationTemplateAlreadyExists`, 409). `PreviewCommunicationTemplateHandler` busca la plantilla y delega en `render()`, propagando `MissingTemplateVariable` si corresponde — no persiste nada.
- **Autorización**: nuevos permisos `communication_templates.manage`/`communication_templates.view`. `communication_templates.manage`: SuperAdmin e InstitutionalAdmin. `communication_templates.view`: SuperAdmin, InstitutionalAdmin y **Teacher** — sin `Student` (a diferencia de `achievements.view`/`badges.view`/`challenges.view`, esta es una herramienta interna administrativa/docente, mismo criterio que `road_passports.view`/`certifications.view`/`simulators.view`).
- **API HTTP**: `/api/v1/notification/templates` — `POST`/`PUT {id}`/`POST {id}/retire` bajo `communication_templates.manage`; `GET`/`GET {id}`/`POST {id}/preview` bajo `communication_templates.view` (la vista previa es de solo lectura, no requiere el permiso de gestión).
- **Pruebas**: 42 tests nuevos repartidos en dominio (`CommunicationTemplate` — incluyendo el renderizado, la variable faltante y el placeholder no declarado; `CommunicationTemplateCode`), persistencia (repositorio Eloquent, incluyendo la unicidad por código+idioma), aplicación (handlers con repositorio en memoria) y feature (API HTTP completa, incluyendo el rechazo de `Student` al listar el catálogo).

### Validaciones

- Suite completa del módulo ✅ — `99 passed (248 assertions)`.
- Suite completa de Authorization ✅ — `53 passed (171 assertions)`, confirmando los permisos nuevos.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=notification` ✅ — 14 rutas registradas (las 8 previas de ENG-056/057 + 6 de plantillas).

**Estado:** Finalizado. Con esto cierra por completo la **Fase 11 — Comunicación y notificaciones** (ENG-056 a ENG-058).

## 2026-08-27 — IMP-059 (Cierre de ENG-059 — Panel administrativo API)

### Alcance acordado con el usuario

Primera historia de la Fase 12 — Administración y operación. A diferencia de las historias anteriores, cubre siete áreas del roadmap con niveles de madurez muy distintos; antes de acordar el alcance se investigó el estado real del backend (agentes de exploración) para no proponer reconstruir lo que ya existía. Hallazgo: Cursos y Evaluaciones ya tenían CRUD completo y maduro en `Modules\Academic` (sin trabajo nuevo); Usuarios no tenía ninguna API administrativa; Organizaciones solo tenía listar y crear; Reportes, Configuración y Operación del sistema no existían (*greenfield*); Auditoría existía como servicio interno de escritura sin capa HTTP ni método de consulta. Alcance acordado: Usuarios — listar, ver detalle, activar/desactivar (sin reseteo de contraseña, acciones masivas ni impersonación); Organizaciones — agregar ver detalle y actualizar (renombrar); Reportes — un único endpoint de resumen agregado con conteos simples, sin motor configurable; Configuración — almacén clave-valor simple; Operación — salud agregada (solo conectividad a base de datos) y lectura de auditoría existente. Detalle en `docs/plans/2026-08-27-panel-administrativo-eng059-design.md`.

### Completado

- **Identity (Usuarios)**: `UserRepository::all()` nuevo (interfaz + Eloquent). `Application\Responses\UserResponse`. `ListUsersUseCase`/`GetUserUseCase` (nuevos). `DeactivateUserCommand`/`DeactivateUserResponse`/`DeactivateUserUseCase`, mismo patrón que `ActivateUserUseCase` ya existente — "suspender" se mapea a `User::deactivate()` (transición a `Inactive`), sin introducir un concepto nuevo de dominio. Controladores `ListUsersController`/`ShowUserController`/`DeactivateUserController`, mismo estilo invocable-por-acción que `ActivateUserController`. Nuevas rutas en `/api/v1/users` (`users.view`/`users.manage`); la ruta de activación de autoservicio existente (`/api/v1/auth/users/{userId}/activate`, sin permiso) se conserva intacta, y se agrega una segunda ruta administrativa reutilizando el mismo controlador bajo `permission:users.manage`.
- **Organization**: `Organization::rename(OrganizationName): void` nuevo. `GetOrganizationQuery`/`GetOrganizationHandler` (reutiliza `OrganizationListItemResponse`) y `UpdateOrganizationCommand`/`UpdateOrganizationHandler`. Rutas `GET /{organizationId}` (mismo criterio que `index`, sin permiso adicional) y `PUT /{organizationId}` (`organizations.manage`, mismo permiso que `store`).
- **Audit**: `AuditRepository::all(): list<AuditEntry>` nuevo (antes solo `save()`). `AuditEntry` ganó `?id`/`?occurredAt` opcionales para soportar la lectura (los escribe la base de datos, no el llamador). `EloquentAuditRepository::all()` ordena por `occurred_at` descendente.
- **Nuevo módulo `Modules\Admin`** (bounded context de la Fase 12): agregado `SystemSetting` (`key`/`value`/`changedAt` — el campo se llama `changedAt`, no `updatedAt`, para evitar la colisión con la columna automática de Eloquent, mismo criterio que `Simulator::registeredAt()`); VO `SystemSettingKey` (regex `^[a-z][a-z0-9_]*$`). `SystemSummaryRepository`/`EloquentSystemSummaryRepository` — lee directamente `UserModel`/`EnrollmentModel`/`UserAchievementModel`/`CertificateModel`/`SimulationSessionModel` de otros módulos para producir conteos; documentado como excepción deliberada al aislamiento entre módulos, limitada a este reporte de solo lectura sin invariantes de dominio. `GetSystemHealthHandler` verifica conectividad a base de datos con `DB::select('SELECT 1')` envuelto en `try`/`catch`. `GetAuditLogsHandler` depende de `Modules\Audit\Application\Contracts\AuditRepository` (dependencia entre módulos documentada: "Operación del sistema" es una preocupación administrativa, no de auditoría en sí).
- **Autorización**: permisos nuevos `users.manage`/`users.view`/`reports.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que el resto de la sesión) y `system_settings.manage`/`system_settings.view`/`system_operations.view` (**únicamente SuperAdmin**, mismo criterio que `roles.manage` — configuración y operación global del sistema, no una preocupación por institución).
- **API HTTP**: `/api/v1/users` (Identity), `/api/v1/organizations/{id}` show/update (Organization), y `/api/v1/admin/{settings,reports,operations}` (Admin, 7 rutas nuevas).
- **Pruebas**: se corrigió además una regresión real detectada al correr la suite completa por primera vez esta sesión: `InMemoryVerificationUserRepository` (fake en `Modules\Certification\Tests\Unit\Application\VerifyCertificateHandlerTest.php`) no implementaba el nuevo método `UserRepository::all()`, causando un error fatal de clase abstracta — se corrigió agregando el método con el mismo patrón `throw new LogicException('No usado en esta prueba.')` que ya usaban los demás métodos no usados de ese fake.

### Validaciones

- Suite combinada de los cinco módulos tocados (`Identity`, `Organization`, `Authorization`, `Audit`, `Admin`) ✅ — `156 passed (425 assertions)`.
- Suite de `Certification` tras corregir la regresión ✅ — `59 passed (129 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=admin` ✅ — 7 rutas registradas.

**Estado:** Finalizado.

## 2026-08-27 — IMP-060 (Cierre de ENG-060 — Gestión de archivos)

### Alcance acordado con el usuario

Segunda historia de la Fase 12 — Administración y operación. A diferencia de ENG-059, introduce un concepto de dominio propio (archivos almacenados) que no es una preocupación administrativa en sí, así que se le da un módulo nuevo, `Modules\FileStorage`, en vez de extender `Modules\Admin`: las fases del roadmap son agrupaciones de planificación, no límites de contexto acotado de DDD. Investigación previa (agentes de exploración): el contenedor MinIO ya existía en `compose.yaml` (servicio `minio`, credenciales `edudrive`/`edudrive_local_password`) pero no estaba conectado — faltaba `league/flysystem-aws-s3-v3` y las variables `AWS_*` en `.env`, aunque el disco `s3` en `config/filesystems.php` ya tenía la forma correcta; no existía ningún servicio antivirus, ningún módulo/agregado/endpoint de archivos ni ningún concepto de cuota — completamente *greenfield*. Alcance acordado: MinIO conectado de verdad; antivirus como un simple estado `pending`/`clean`/`infected` sin integración real con ningún motor, expuesto como ajuste manual (`files.manage`) para cuando exista un escáner real; carga por el backend (multipart) y descarga vía URL temporal firmada, nunca reenviando bytes a través de Laravel; cuota simple por usuario verificada sumando el tamaño de los archivos ya guardados, leída de una clave de `SystemSetting` (`file_storage_quota_bytes`, ya construido en ENG-059) con un valor por defecto. Detalle completo en `docs/plans/2026-08-27-gestion-archivos-eng060-design.md`.

### Completado

- **Dominio**: nuevo módulo `Modules\FileStorage` con el agregado `StoredFile` (`ownerId`, `originalFilename`, `mimeType`, `sizeBytes`, `storagePath`, `scanStatus`, `uploadedAt` — el campo se llama `uploadedAt`, no `createdAt`, mismo criterio que `Simulator::registeredAt()`/`SystemSetting::changedAt()`); enum cerrado `FileScanStatus` (`pending`/`clean`/`infected`, empieza siempre en `pending`); `isOwnedBy()` para el patrón anti-fuga.
- **MinIO**: se instaló `league/flysystem-aws-s3-v3` (`composer require`, arrastra `aws/aws-sdk-php`); variables `AWS_*` agregadas a `.env`/`.env.example` apuntando al contenedor `minio` ya corriente (`AWS_ENDPOINT=http://minio:9000`, `AWS_USE_PATH_STYLE_ENDPOINT=true`). `Application\Contracts\FileStorage` (`store`/`delete`/`temporaryDownloadUrl`) implementada por `Infrastructure\Storage\S3FileStorage` sobre `Storage::disk('s3')`. `php artisan files:ensure-bucket` (comando de consola idempotente con el SDK de AWS directo, mismo espíritu que las migraciones — un paso de aprovisionamiento explícito, no un efecto secundario oculto en cada arranque) — verificado end-to-end contra el contenedor real: primera corrida crea el bucket, segunda confirma que ya existe.
- **Persistencia**: tabla `stored_files` (`owner_id` con FK a `users`, `storage_path` varchar(500)). `FileRepository` con su implementación Eloquent, incluyendo `totalBytesForOwner()` (suma agregada para la verificación de cuota) y `delete()` como borrado real de fila (a diferencia de `Achievement`/`Badge`/`Challenge`, que nunca se borran — un archivo eliminado libera espacio real contra la cuota).
- **CQRS**: `UploadFileCommand`/`UploadFileHandler` (verifica cuota — `totalBytesForOwner() + tamaño nuevo` contra el límite leído de `Modules\Admin\Domain\Repositories\SystemSettingRepository`, con 100 MB de valor por defecto si la clave no está configurada — **antes** de llamar a `FileStorage::store()`, para no dejar objetos huérfanos en el bucket si se rechaza la carga); `GetFileQuery`/`GetFileDownloadUrlQuery`/`DeleteFileCommand` con el patrón anti-fuga de pertenencia (`FileNotFound` uniforme para archivo inexistente o ajeno sin permiso, mismo criterio que `RoadPassport`/`SimulationSession`/`Notification`); `GetMyFilesQuery` de autoservicio; `SetFileScanStatusCommand` sin dimensión de pertenencia (gestión únicamente). Dependencia entre módulos documentada: `Modules\FileStorage` depende de `Modules\Admin\Domain\Repositories\SystemSettingRepository`, mismo criterio que la dependencia de `Modules\Admin` sobre `Modules\Audit` en ENG-059.
- **Autorización**: permisos nuevos `files.manage`/`files.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que `users.manage`/`reports.view`); autoservicio de carga, listado propio y eliminación propia sin permiso especial para cualquier usuario autenticado (a diferencia de unirse a un `Challenge`, subir un archivo propio es una acción básica de la plataforma, no una acción con efecto en otro usuario).
- **API HTTP**: `/api/v1/files` — `POST /` (autoservicio, `UploadFileRequest` con tope de 20 MB por archivo), `GET /me`, `GET /{id}` y `GET /{id}/download-url` (anti-fuga vía `files.view`), `DELETE /{id}` (autoservicio o `files.manage`), `PUT /{id}/scan-status` (únicamente `files.manage`). El estado de escaneo no bloquea la descarga (como ningún mecanismo real lo cambia todavía, bloquear mientras esté `pending` haría que ningún archivo fuera descargable jamás; queda diferido a cuando exista un escáner real).
- **Pruebas**: 4 unitarias de dominio, 14 unitarias de aplicación (con fakes en memoria, incluyendo el rechazo de cuota sin escritura en el almacenamiento), 5 de integración del repositorio Eloquent y 16 de feature HTTP (incluyendo una subida real contra el contenedor MinIO en ejecución) — 40 tests en total para el módulo.

### Validaciones

- Suite de `Modules\FileStorage` ✅ — `40 passed (77 assertions)`, incluyendo una subida real de bytes contra el contenedor MinIO.
- Suite combinada de `Modules\FileStorage` + `Modules\Admin` tras el layer de persistencia/CQRS ✅ — `64 passed (128 assertions)`.
- Suite de `Modules\Authorization` tras agregar `files.manage`/`files.view` ✅ — `56 passed (199 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=files` ✅ — 7 rutas registradas.

**Estado:** Finalizado.

## 2026-08-27 — IMP-061 (Cierre de ENG-061 — Importaciones masivas)

### Alcance acordado con el usuario

A diferencia de ENG-059/ENG-060, no introduce ningún módulo nuevo: extiende `Modules\Identity` (usuarios/estudiantes) y `Modules\Academic` (cursos, preguntas) con un mecanismo de importación masiva por archivo CSV. Investigación previa: "Estudiante" no es un concepto propio en el backend — es un `User` con el rol `Student` asignado vía `Modules\Authorization`; "Grupo" no existe en absoluto (ni agregado, ni tabla, ni concepto equivalente — búsqueda exhaustiva sin resultados); Cursos y Preguntas ya tenían creación individual madura en `Modules\Academic`, pero Preguntas requiere un `competencyId` existente y estructuras anidadas (`response`/`options`) dependientes del tipo; no había ninguna librería CSV/Excel instalada. Alcance acordado: Grupos diferido por completo (ninguna base sobre la cual importar — historia propia futura); Usuarios y Estudiantes unificados en un solo mecanismo de importación (columna `role` opcional, `Student` por defecto); validación previa integrada en la misma operación, sin modo "solo validar" separado (el reporte de resultados por fila cumple ese propósito); procesamiento síncrono en la misma petición HTTP, sin colas ni jobs. Detalle completo en `docs/plans/2026-08-27-importaciones-masivas-eng061-design.md`.

### Completado

- **Patrón compartido por los tres importadores**: cada uno reutiliza directamente el handler de creación individual ya existente por fila (mismo criterio arquitectónico que `CreateBulkEnrollmentsHandler`, ya presente en `Modules\Academic` antes de esta historia), capturando `DomainException` (usa `errorCode()`) o cualquier `Throwable` inesperado (reportado como `IMPORT_ROW_INVALID`) sin detener el resto del lote, y acumulando un reporte `total`/`created`/`failed`/`results[]` con el número de fila 1-indexado. `league/csv` (nuevo, `composer require`) parsea el archivo en la capa HTTP — el dominio/aplicación nunca conoce la librería de parseo, igual que `UploadFileCommand` (ENG-060) recibe una ruta de archivo en vez de un `UploadedFile`. Límite de 500 filas por archivo validado en el controlador (`ValidationException` reutilizando el renderizador global de errores 422).
- **Identity (Usuarios + Estudiantes unificado)**: `BulkImportUsersCommand`/`BulkImportUsersUseCase` (mismo patrón `execute()` sin bus que el resto de Identity) reutiliza `RegisterUserUseCase` y despacha `Modules\Authorization\Application\Commands\AssignRoleCommand` vía `CommandBus` — dependencia cruzada deliberadamente superficial (solo el DTO del comando, no el repositorio ni el handler de Authorization) para evitar un ciclo con la dependencia ya existente de Authorization hacia `Identity\Domain\Repositories\UserRepository`. Cada fila se envuelve en `DB::transaction()` para que un rol inválido no deje un usuario huérfano sin rol asignado. Ruta `POST /api/v1/users/import` bajo `users.manage` (a diferencia del registro individual, que es autoservicio público).
- **Academic (Cursos)**: `BulkImportCoursesCommand`/`Handler` reutiliza `CreateCourseHandler` por fila; columnas opcionales vacías se normalizan a `null`. Ruta `POST /api/v1/academic/courses/import` bajo `courses.manage`, agregada al `CourseController` existente (mismo criterio que `EnrollmentController::bulk()` ya presente junto al alta individual).
- **Academic (Preguntas)**: `BulkImportQuestionsCommand`/`Handler` resuelve `competency_code` (no el UUID interno, poco práctico de escribir a mano) contra `CompetencyRepository::findByCode()` antes de reutilizar `CreateQuestionHandler`; las columnas `response`/`options`/`media`/`license_categories` — estructuras anidadas dependientes de `QuestionType`, incompatibles con una fila CSV intrínsecamente plana — se codifican como celdas JSON, decodificadas con `json_decode(..., JSON_THROW_ON_ERROR)` y normalizadas a la forma exacta que exige `CreateQuestionCommand` (mismo mapeo `ref_id`→`refId` que ya usa `QuestionController::normalizeOptions()`). Ruta `POST /api/v1/academic/questions/import` bajo `questions.manage`.
- **Pruebas**: 5 feature + 0 unit para Usuarios (Identity no tiene precedente de tests unitarios con fakes; se siguió su convención existente de solo feature tests con servicios reales); 4 unit + 5 feature para Cursos; 5 unit + 4 feature para Preguntas — 23 tests nuevos en total, todos verificando el reporte de éxito parcial por fila (fila inválida no detiene el resto del lote).

### Validaciones

- Suite de `Modules\Identity` + `Modules\Authorization` tras el importador de usuarios ✅ — `80 passed (266 assertions)`.
- Suite de `Modules\Academic` ejecutada en lotes acotados por el límite de memoria del entorno de pruebas (ya documentado en IMP-059) ✅ — `670 tests` en total entre los distintos lotes, con una única falla preexistente y no relacionada (`GradingResultTest`, dominio de calificación de exámenes, no tocado por esta historia).
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=import` ✅ — 3 rutas registradas (`users/import`, `courses/import`, `questions/import`).

**Estado:** Finalizado.

## 2026-08-27 — IMP-062 (Cierre de ENG-062 — Exportaciones)

### Alcance acordado con el usuario

A diferencia de ENG-061, el roadmap no especifica QUÉ datos se exportan, solo los mecanismos (CSV, XLSX, PDF, exportaciones asíncronas, control de acceso, auditoría). Investigación previa: ninguna consulta de listado tiene paginación hoy; `league/csv` (ya instalada en ENG-061) también sabe escribir CSV, pero nada la usaba para eso; no existe ninguna librería de XLSX ni PDF; no existe ningún `ShouldQueue` job en todo el backend — sería el primero — y `compose.yaml` no tiene worker de cola corriendo; `Modules\Audit` ya expone un `AuditLogger::log()` genérico, usado hoy solo desde `Identity`; `Modules\FileStorage` (ENG-060) ya expone una interfaz de bajo nivel (`store`/`temporaryDownloadUrl`) independiente del agregado `StoredFile` y su cuota. Alcance acordado: solo CSV (XLSX y PDF diferidos — cada uno requeriría una librería nueva y su renderizado); conjunto fijo y reducido de tres exportadores concretos — Auditoría, Cursos, Enrollments — reutilizando las consultas de listado ya existentes, en vez de un framework genérico; procesamiento síncrono en la misma petición HTTP, sin cola de trabajos; permiso nuevo y transversal `exports.view` (SuperAdmin + InstitutionalAdmin, mismo patrón que `reports.view`/`system_operations.view`) en vez de reutilizar el `.view` de cada recurso, porque exportar todas las filas de una vez es un riesgo distinto a ver una lista paginada. Detalle completo en `docs/plans/2026-08-27-exportaciones-eng062-design.md`.

### Completado

- **Sin módulo nuevo**: cada exportador vive en el módulo dueño de los datos (`Modules\Admin` para Auditoría, `Modules\Academic` para Cursos y Enrollments), mismo criterio que ENG-061.
- **Infraestructura compartida en `Modules\Foundation`** (única excepción al criterio anterior, por ser infraestructura pura sin reglas de negocio, usada idénticamente por los tres exportadores): `Infrastructure\Export\CsvWriter` (envuelve `League\Csv\Writer`); `Infrastructure\Export\ExportFileWriter` (escribe el CSV a un archivo temporal, lo sube vía `Modules\FileStorage\Application\Contracts\FileStorage::store()` — la interfaz de bajo nivel, sin crear una fila `StoredFile` ni contar contra la cuota de un usuario, porque un archivo exportado es un artefacto generado por el sistema, no un adjunto propio — y devuelve una URL temporal de 15 minutos vía `temporaryDownloadUrl()`); `Application\Responses\ExportResponse` (DTO de respuesta genérico: `url`, `expires_at`, `row_count`, `format`). Extraída tras implementar el primer exportador (Auditoría) y notar que repetir la misma lógica de archivo temporal en los otros dos sería peor que centralizarla una vez.
- **Admin (Auditoría)**: `ExportAuditLogsCommand`/`Handler` reutiliza `AuditRepository::all()` (la misma fuente que `GetAuditLogsHandler`), agregado a `SystemOperationController` existente. Ruta `POST /api/v1/admin/operations/audit-logs/export`.
- **Academic (Cursos)**: `ExportCoursesCommand`/`Handler` reutiliza `CourseRepository::all()` (la misma fuente que `ListCoursesHandler`), agregado a `CourseController` existente. Ruta `POST /api/v1/academic/courses/export`.
- **Academic (Enrollments)**: `ExportEnrollmentsCommand`/`Handler` reutiliza `EnrollmentRepository::all()` sin filtros (la misma fuente que `ListEnrollmentsHandler`, pero sin los parámetros de filtro — esta historia exporta el conjunto completo), agregado a `EnrollmentController` existente junto a `bulk()`. Ruta `POST /api/v1/academic/enrollments/export`.
- **Auditoría de cada exportación**: los tres handlers llaman a `Modules\Audit\Application\Services\AuditLogger::log()` tras generar el archivo (`export.audit_logs`/`export.courses`/`export.enrollments`, con `row_count`/`format` en los metadatos) — sin entidad/id propios, por tratarse de una exportación masiva y no la acción sobre un recurso puntual.
- **Autorización**: permiso nuevo `exports.view` (SuperAdmin + InstitutionalAdmin) protege los tres endpoints, independientemente del permiso `.view` de cada recurso.
- **Pruebas**: 3 unitarias de `CsvWriter`; 3+2+2 unitarias de los tres handlers (con fakes de `FileStorage`/`AuditLogger`, nombrados con sufijo por historia para evitar colisiones de clases globales entre archivos de test); 4+3+6 de feature HTTP (incluyendo una exportación real contra el contenedor MinIO en ejecución) — 23 tests nuevos en total.

### Validaciones

- Suite de `Modules\Foundation` ✅ — `9 passed`.
- Suite de `Modules\Admin` + `Modules\Authorization` + `Modules\FileStorage` tras el exportador de auditoría ✅ — `144 passed (387 assertions)`, incluyendo una exportación real contra MinIO.
- Suites dirigidas de `Modules\Academic` (exportadores nuevos + `EnrollmentTest`/`CreateCourseTest`/`BulkEnrollmentHandlerTest` ya existentes, ejecutadas en lotes acotados por el límite de memoria del entorno, igual que en IMP-059/IMP-061) ✅ — sin fallas.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=export` ✅ — 3 rutas registradas.

**Estado:** Finalizado.

## 2026-08-27 — IMP-063 (Cierre de ENG-063 — Reportes académicos)

### Alcance acordado con el usuario

Primera historia de la Fase 13 — Reportes y analítica. El roadmap lista seis reportes (Progreso, Rendimiento, Aprobación, Competencias, Actividad, Comparación por grupo) sin especificar cómo se calculan ni sobre qué se agrupan. Investigación previa: ninguna consulta de listado en `Modules\Academic` hace agregación SQL — todo el patrón existente (`PracticalResultCalculator`, `RoadPassportTrustCalculator`) es traer filas y calcular en PHP; `EnrollmentProgressRepository` solo consulta una inscripción a la vez; `ExamAttemptRepository::all()`/`ExamRepository::all()` ya soportan traer todos los intentos/exámenes de un curso, combinables sin cambios de esquema; "Actividad" no tenía ningún dato base (`User` no registraba ninguna marca de tiempo de sesión); "Grupo" (cohorte/sección) no existe como concepto en el backend, confirmado también en ENG-061. **Única historia de la sesión en la que el usuario rechazó explícitamente la opción recomendada**: se propuso reducir a tres reportes (unificando Rendimiento/Aprobación y difiriendo Actividad) y el usuario pidió los seis completos. Para "Comparación por grupo" sí se aceptó la reinterpretación propuesta: "por curso" en vez de un concepto de grupo inexistente — cada reporte acepta una lista de `course_ids` en vez de existir un endpoint de "comparar" separado. Calculado al vuelo sin persistencia (mismo patrón que los calculadores ya existentes); reutiliza `reports.view` (ENG-059) sin permiso nuevo. Detalle completo en `docs/plans/2026-08-27-reportes-academicos-eng063-design.md`.

### Completado

- **Identity — base para Actividad**: `User::recordLogin(DateTimeImmutable): void` nuevo + campo `lastLoginAt` (nullable, columna nueva vía migración separada, no se edita la migración original de `users`). `LoginUserUseCase::execute()` llama a `recordLogin()` y guarda el usuario en cada inicio de sesión exitoso — cubre tanto el login web (`LoginWebController`) como el de API, ya que ambos comparten el mismo caso de uso.
- **Cinco reportes en `Modules\Academic`**, todos con la forma `Get{X}ReportQuery(list<string> $courseIds = [])` → `Get{X}ReportHandler` → `list<{X}ReportResponse>` (una fila por curso; sin `course_ids` cubre todos los cursos vía `CourseRepository::all()`):
  - **Progreso**: reutiliza `CourseLessonCatalog` (ya existente) para el total de lecciones del curso y `EnrollmentProgressRepository::findByEnrollmentId()` por cada inscripción — promedio de % completado y conteo de inscripciones 100% completas.
  - **Rendimiento** y **Aprobación**: comparten la misma fuente (intentos de examen `Submitted` de todos los exámenes del curso) a través de un servicio nuevo, `CourseExamAttemptsLookup` (curso → exámenes vía `ExamRepository::all($courseId)` → intentos vía `ExamAttemptRepository::all(examId:, status: Submitted)`), para no traer los intentos dos veces aunque se expongan como dos reportes separados (honrando que el usuario los quiso distintos).
  - **Competencias**: agrega `competencyBreakdown()` de todos los intentos del curso, agrupando por `competencyId` (promedio de porcentaje, tamaño de muestra), resolviendo el código de competencia vía `CompetencyRepository::findById()`.
  - **Actividad**: por cada inscripción del curso, resuelve el `User` vía `Modules\Identity\Domain\Repositories\UserRepository::findById()` (dependencia entre módulos documentada, mismo contrato público que ya usa `Modules\Authorization`) — cuenta activos en los últimos 30 días (umbral fijo), nunca-han-iniciado-sesión, y promedio de días desde el último login.
  - **`ReportCourseResolver`** (compartido por los cinco): resuelve `course_ids` → `list<Course>`, o todos los cursos si la lista viene vacía; lanza `CourseNotFound` si un id explícito no existe.
- **API HTTP**: `GET /api/v1/academic/reports/{progress,performance,approval,competencies,activity}?course_ids[]=...`, los cinco bajo `permission:reports.view` (reutilizado, sin permiso nuevo), agregados a un controlador nuevo, `AcademicReportController`.
- **Pruebas**: 5 unitarias (una por reporte, con fixtures reales vía `ExamAttempt::restore()` construido directamente en vez de recorrer todo el flujo start→answer→submit, ya que `restore()` no valida consistencia entre `GradingResult` y el desglose — solo cada `CompetencyGrade` valida sus propios invariantes) + 5 de feature HTTP (permisos y filtro por `course_ids`) para los reportes, más 2 nuevas (feature + integración) para `lastLoginAt` en Identity — 12 tests nuevos en total.

### Validaciones

- Suite de `Modules\Identity` + `Modules\Authorization` ✅ — `83 passed (274 assertions)`.
- Suites dirigidas de `Modules\Academic` (los cinco reportes + feature) ✅ — `10 passed (52 assertions)`; PHPStan nivel 8 y Pint limpios sobre el módulo completo (ejecutado en lotes acotados por el límite de memoria del entorno, igual que en IMP-059/061/062).
- `php artisan route:list --path=academic/reports` ✅ — 5 rutas registradas.

**Estado:** Finalizado.

## 2026-08-28 — IMP-064 (Cierre de ENG-064 — Reportes de simulación)

### Alcance acordado con el usuario

Segunda historia de la Fase 13 — Reportes y analítica. Mismo patrón ambiguo que ENG-063: el roadmap lista seis reportes sin especificar cómo se calculan ni sobre qué se agrupan. Investigación previa: ningún repositorio de `Modules\Simulation` filtra por nada hoy — `SimulationSessionRepository::all()`/`allForUser()` no aceptan parámetros, `TelemetryEventRepository`/`DecisionPointRepository` solo tienen `allForSession()` — aún más plano que `Modules\Academic` antes de ENG-063. `TelemetryEventType` (enum cerrado `Collision`/`Infraction`/`SignalUsage`/`Critical`) ya distingue "Infracción" como su propio caso, así que "Errores frecuentes" e "Infracciones" comparten exactamente la misma fuente. `PracticalResultCalculator`/`DecisionEngineCalculator` son servicios de dominio puros sin dependencias propias, ya calculan resultado/riesgo por sesión individual. `competenciesDemonstrated` es hoy una sola cadena de texto libre por sesión, sin ninguna estructura real que agregar. Alcance acordado: cuatro reportes — Sesiones, Errores e infracciones (unificados), Evolución y Riesgos detectados; agregados por usuario (`allForUser()` ya existente) en vez de por simulador (requeriría un método de repositorio nuevo); Competencias prácticas diferido por completo. Detalle completo en `docs/plans/2026-08-28-reportes-simulacion-eng064-design.md`.

### Completado

- **Cuatro reportes en `Modules\Simulation`**, todos con la forma `Get{X}ReportQuery(list<string> $userIds = [])` → `Get{X}ReportHandler` → `list<{X}ReportResponse>` (una fila por usuario; sin `user_ids` descubre todos los usuarios con sesiones existentes iterando `SimulationSessionRepository::all()`):
  - **Sesiones**: por usuario, conteo total/completadas/canceladas y duración promedio (`actualDurationMinutes()`, ya existente) de las sesiones completadas.
  - **Errores e infracciones** (unifica los dos puntos del roadmap): por cada sesión completada del usuario, cuenta la frecuencia de `TelemetryEvent` por cada caso de `TelemetryEventType`.
  - **Evolución**: secuencia cronológica de resultados por sesión completada, reutilizando directamente `PracticalResultCalculator::calculate(session, events)` (servicio de dominio puro, sin dependencias) por cada sesión — `sessionId`/`scenario`/`scheduledAt`/`score`/`outcome` por entrada.
  - **Riesgos detectados**: por cada sesión completada, reutiliza `DecisionEngineCalculator::calculate(sessionId, points)` y agrega entre sesiones — conteo apropiado/inapropiado global, promedio del `consistencyScore` ya calculado por sesión, y reacciones inapropiadas agrupadas por `DecisionRiskLevel` (la señal directamente accionable: en qué nivel de riesgo el conductor reaccionó mal, no solo el conteo total de puntos de decisión).
  - **`ReportUserIdsResolver`** (compartido por los cuatro, análogo a `ReportCourseResolver` de ENG-063): resuelve `user_ids` explícitos o descubre todos los usuarios con sesiones si la lista viene vacía. A diferencia de `ReportCourseResolver`, no valida que el usuario "exista" — no hay un agregado `User` que consultar desde `Modules\Simulation`, así que un `user_id` sin sesiones simplemente produce un reporte vacío, sin error.
- **API HTTP**: `GET /api/v1/simulation/reports/{sessions,telemetry,evolution,risks}?user_ids[]=...`, los cuatro bajo `permission:reports.view` (reutilizado, sin permiso nuevo), agregados a un controlador nuevo, `SimulationReportController`.
- **Pruebas**: 5 unitarias (con fakes en memoria — `InMemoryReportSessionRepository`/`InMemoryReportEventRepository`/`InMemoryReportDecisionPointRepository`, mismo estilo ya usado por `PracticalResultHandlerTest`/`DecisionEngineHandlerTest` en este módulo, a diferencia del estilo "repositorio real vía `app()`" usado en `Modules\Academic`) + 4 de feature HTTP (permisos) — 9 tests nuevos en total.

### Validaciones

- Suite completa de `Modules\Simulation` ✅ — `190 passed (480 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=simulation/reports` ✅ — 4 rutas registradas.

**Estado:** Finalizado.

## 2026-08-28 — IMP-065 (Cierre de ENG-065 — Indicadores institucionales)

### Alcance acordado con el usuario

Tercera y última historia de la Fase 13 — Reportes y analítica. Mismo patrón ambiguo que ENG-063/064: el roadmap lista seis indicadores sin especificar cómo se calculan ni sobre qué se agrupan. Investigación previa: `Organization` tiene `Campus` como entidad hija con id propio, pero nada la referencia — ni `Enrollment` ni `RoleAssignment` tienen `campusId`, solo `organizationId` — así que "Uso por sede" no tiene ningún dato hoy. `Certificate` (Certification), `RoadPassport` y los agregados de Gamification no tienen ningún campo de organización — "Impacto" no tiene ningún vínculo organizacional real en ninguna fuente candidata, sería una métrica especulativa. `EnrollmentRepository::all()` ya filtra por `organizationId` a nivel SQL, suficiente para recortar inscripciones por organización sin cambios de esquema. `ExamAttemptRepository`/`ExamRepository` no tienen ningún concepto de organización — un indicador de desempeño institucional requiere cruzar inscripciones → cursos → exámenes → intentos, filtrando a los usuarios inscritos institucionalmente en esa organización. Alcance acordado: cuatro indicadores — Participación, Finalización, Desempeño y Adopción — todos agregados por organización; Impacto y Uso por sede diferidos. Detalle completo en `docs/plans/2026-08-28-indicadores-institucionales-eng065-design.md`.

### Completado

- **Viven en `Modules\Academic`**, no en `Modules\Organization`: tres de los cuatro indicadores son mayoritariamente datos de Academic (Enrollment/EnrollmentProgress/ExamAttempt); Organization solo aporta la lista de organizaciones sobre la cual iterar — mismo criterio que el reporte de Actividad de ENG-063, que vivió en Academic aunque dependiera de `Identity\UserRepository`.
- **`ReportOrganizationResolver`** (análogo a `ReportCourseResolver`/`ReportUserIdsResolver`): resuelve `organization_ids` explícitos, validando existencia vía `OrganizationRepository::findById()` — a diferencia de `ReportUserIdsResolver` (ENG-064), que no valida porque no hay un agregado `User` que consultar desde Simulation, aquí sí existe `Organization` como agregado real, así que un id inexistente lanza `Organization\Application\Exceptions\OrganizationNotFound` (reutilizada tal cual desde `Modules\Organization` — dependencia entre módulos documentada, el manejador de excepciones global ya renderiza cualquier `DomainException` ajena por su `errorCode()`/`statusCode()`). Sin `organization_ids`, cubre todas las organizaciones vía `OrganizationRepository::all()`.
- **Cuatro indicadores**, todos con la forma `Get{X}ReportQuery(list<string> $organizationIds = [])` → `Get{X}ReportHandler` → `list<{X}ReportResponse>` (una fila por organización):
  - **Participación**: distingue inscripción de participación real — cuenta inscripciones con al menos una lección completada (`EnrollmentProgress::completedLessonIds() !== []`), no solo inscripciones activas.
  - **Finalización**: proporción de inscripciones en estado `Completed` (estado terminal real, distinto de `Active`/`Canceled`).
  - **Desempeño**: reutiliza `CourseExamAttemptsLookup` (ya construido en ENG-063) por cada curso con inscripciones institucionales en la organización, filtrando los intentos devueltos a solo los `userId` inscritos institucionalmente en esa organización para ese curso — evita contar intentos de estudiantes de otras instituciones o autoinscritos individualmente en el mismo curso (verificado explícitamente con una prueba: un intento ajeno a la organización no se cuenta).
  - **Adopción**: primera serie temporal de toda la Fase 13 — inscripciones nuevas agrupadas por mes (`enrolledAt` formateado `Y-m`), en orden cronológico, mismo patrón fetch-all-then-compute que el resto de los cálculos derivados de la sesión.
- **API HTTP**: `GET /api/v1/academic/reports/organizations/{participation,completion,performance,adoption}?organization_ids[]=...`, los cuatro bajo `permission:reports.view` (reutilizado, sin permiso nuevo), agregados a un controlador nuevo, `OrganizationReportController`.
- **Pruebas**: 6 unitarias (con repositorios reales vía `app()`, mismo estilo que `AcademicReportHandlerTest` de ENG-063, incluyendo una prueba dedicada a la exclusión de intentos ajenos a la organización y otra al `OrganizationNotFound`) + 4 de feature HTTP (permisos) — 10 tests nuevos en total.

### Validaciones

- Suites dirigidas de `Modules\Academic` (los cuatro indicadores nuevos) ✅ — `10 passed (38 assertions)`.
- Suite completa de `Modules\Organization` (sin cambios propios, verificada por la nueva dependencia cruzada) ✅ — `42 passed (96 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.
- `php artisan route:list --path=academic/reports/organizations` ✅ — 4 rutas registradas.

Con esto cierra por completo la **Fase 13 — Reportes y analítica** (ENG-063 a ENG-065).

**Estado:** Finalizado.

## 2026-08-28 — IMP-067 (Cierre de ENG-067 — Rate limiting)

### Alcance acordado con el usuario

Primera historia de la Fase 14 — Seguridad y cumplimiento. A diferencia de las historias recientes (reportes), Laravel ya trae soporte de primera clase para esto (`throttle:` + `RateLimiter::for()`), así que es mecánica de aplicar, aunque completamente greenfield — no existía ningún rate limiting en el backend, ni un alias de middleware registrado. Investigación previa: además de Login (`POST /api/v1/auth/login`, `POST /login`) y Registro (`POST /api/v1/auth/register`) ya conocidos, se encontró que `POST /api/v1/auth/users/{userId}/activate` también es público (sin `auth:sanctum`) — un endpoint no identificado antes de investigar, presumiblemente para un flujo de activación por enlace de correo. "Recuperación de contraseña" no existe en absoluto: ninguna ruta, controlador, ni lógica la implementa; `password_reset_tokens` es una tabla del scaffold de Laravel sin ningún consumidor — no se puede aplicar rate limiting a una funcionalidad que no existe. "Integraciones" son exactamente las dos rutas `simulator.auth` de `Modules\Simulation` (telemetría y decisiones). "Endpoints públicos" es la verificación pública de certificados. Alcance acordado: Login, Registro (incluida la activación pública), Integraciones y Endpoints públicos; Recuperación de contraseña diferida por completo. Detalle completo en `docs/plans/2026-08-28-rate-limiting-eng067-design.md`.

### Completado

- **Limitadores nombrados en `Modules\Foundation`** (`FoundationServiceProvider::boot()`, hasta ahora vacío): `login` (`Limit::perMinute(5)->by(email|ip)` — no solo IP, patrón estándar de Laravel contra *credential stuffing* dirigido a una cuenta específica desde múltiples IPs o contra múltiples cuentas desde una IP), `register` (5/min por IP), `activate` (10/min por IP — más permisivo porque un usuario legítimo puede reintentar un enlace de correo), `public-verification` (30/min por IP), `simulator-integration` (60/min por el `authenticated_simulator_id` que `AuthenticateSimulator` ya adjunta a los atributos de la petición tras autenticar — no por IP, ya que varios simuladores en un mismo laboratorio pueden compartir NAT/IP; aplicado después de `simulator.auth` en la cadena de middleware para poder leer ese atributo).
- **Manejador dedicado para `ThrottleRequestsException`** en `bootstrap/app.php` (código `TOO_MANY_REQUESTS`, estado 429), mismo patrón que los manejadores ya existentes de `ValidationException`/`AuthenticationException`/`DomainException`/`NotFoundHttpException`. No se reenvían los encabezados `Retry-After`/`X-RateLimit-*` del limitador — ningún manejador existente en ese archivo reenvía encabezados tampoco, se mantiene la misma convención.
- **Rutas afectadas**: `POST /api/v1/auth/login` y `POST /login` → `throttle:login`; `POST /api/v1/auth/register` → `throttle:register`; `POST /api/v1/auth/users/{userId}/activate` → `throttle:activate`; `GET /api/v1/certification/verify/{validationCode}` → `throttle:public-verification`; `POST /api/v1/simulation/sessions/{sessionId}/{telemetry,decisions}` → `simulator.auth` + `throttle:simulator-integration`. La ruta administrativa `POST /api/v1/users/{userId}/activate` (bajo `auth:sanctum` + `users.manage`) y el bulk import de usuarios (ENG-061) quedan sin throttle propio — son operaciones de un administrador autenticado, modelo de amenaza distinto al autoservicio anónimo.
- **Pruebas**: 5 en `Modules\Identity` (login API, login web, registro, activación, y una que confirma que el límite de login no se acumula entre correos distintos), 1 en `Modules\Certification` (verificación pública), 3 en `Modules\Simulation` (telemetría, decisiones, y una que confirma que el límite de integración no se comparte entre simuladores distintos) — 9 tests nuevos en total. Se descubrió y corrigió un bug propio durante la escritura: la prueba de login web generaba un correo aleatorio distinto en cada iteración del bucle, por lo que la clave `email|ip` nunca se repetía y el límite nunca se alcanzaba — corregido fijando el correo antes del bucle.

### Validaciones

- Suite de `Modules\Foundation` ✅ — `10 passed`.
- Suite de `Modules\Identity` + `Modules\Certification` ✅ — `91 passed (209 assertions)`.
- Suite completa de `Modules\Simulation` ✅ — `193 passed (485 assertions)`.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia (incluyendo `bootstrap/app.php`, fuera de cualquier módulo).

**Estado:** Finalizado.

## 2026-08-28 — IMP-068 (Cierre de ENG-068 — Auditoría general)

### Alcance acordado con el usuario

Segunda historia de la Fase 14 — Seguridad y cumplimiento. El módulo `Modules\Audit` (`AuditEntry`, `AuditLogger`, `DatabaseAuditLogger`, `EloquentAuditRepository`) ya existía de historias anteriores, con Actor/Acción/Recurso/Fecha bien conectados. Investigación previa encontró: la columna `ip` ya existía en `audit_logs` (scaffold de una migración previa) pero nunca se escribía; Correlation ID no existía en absoluto (ni columna ni campo en el DTO), aunque `Modules\Foundation\Presentation\Http\Middleware\CorrelationId` ya guardaba uno por petición en `Illuminate\Support\Facades\Context`; "Resultado" no existía — solo se auditaban logins exitosos, nunca los fallidos, un hueco real de seguridad y no solo un campo faltante; y de los 3 únicos call sites existentes (`LoginUserUseCase`, `LogoutUserUseCase`, `LogoutAllUsersUseCase`, todos en `Modules\Identity`), `LogoutUserUseCase` nunca establecía el `userId` del actor en su entrada. Hay casi 90 comandos de escritura en todo el backend; auditar todos excede una sola historia. Alcance acordado: autenticación (extendida a login fallido) + asignación de roles (`Modules\Authorization`) + cambios de configuración del sistema (`Modules\Admin`) — las acciones de mayor valor de seguridad, no los ~90 comandos de escritura de todo el backend. Detalle completo en `docs/plans/2026-08-28-auditoria-general-eng068-design.md`.

### Completado

- **Esquema de `audit_logs` completado**: nueva migración agrega `correlation_id` (nullable) y `outcome` (`string`, default `success`); las columnas `ip`/`user_agent` ya existían pero estaban muertas (nunca escritas). `AuditEntry` gana `ip`, `correlationId` y `outcome` (con default `'success'` para no romper los call sites existentes que no lo establecen).
- **Enriquecimiento automático de IP y Correlation ID en la capa de infraestructura**: `DatabaseAuditLogger` ahora inyecta `Illuminate\Http\Request` (el binding de `AuditLogger` ya era no-singleton desde antes, por lo que se resuelve correctamente por petición) y completa `ip` desde `$request->ip()` y `correlationId` desde `Context::get('correlation_id')` únicamente cuando el llamador no los proveyó explícitamente — ningún caso de uso, existente o nuevo, necesita pasarlos manualmente.
- **Login fallido ahora se audita**: `LoginUserUseCase` registra una entrada con `outcome: 'failure'` (email intentado en `metadata`, `userId` si el usuario existe) tanto por credenciales inválidas como por estado que no permite autenticar, y relanza la excepción original sin cambiar el comportamiento HTTP existente.
- **`LogoutUserUseCase` corregido**: gana un parámetro `userId` (ya disponible en `LogoutController` vía `$request->user()`), cerrando el hueco donde el actor de un cierre de sesión nunca quedaba registrado.
- **Asignación de roles auditada**: `AssignRoleCommand`/`AssignRoleHandler` ganan un `actorId` opcional (el usuario autenticado que ejecuta la acción; `null` cuando se dispara desde el comando de consola `authorization:assign-role`, sin actor HTTP) y registran `authorization.role_assigned` con el usuario objetivo, el rol y la organización en `metadata`. Efecto colateral necesario: `BulkImportUsersCommand`/`BulkImportUsersUseCase` (ENG-061), que dispatcha `AssignRoleCommand` internamente, también gana un `actorId` obligatorio threaded desde `BulkImportUsersController`.
- **Cambios de configuración del sistema auditados**: `SetSystemSettingCommand`/`SetSystemSettingHandler` ganan un `actorId` obligatorio (threaded desde `SystemSettingController`) y registran `admin.system_setting_changed` con `old_value`/`new_value` en `metadata` — el valor anterior ya se leía en el handler antes de sobrescribirlo (`findByKey()`), así que "Datos modificados" no requirió ninguna llamada adicional al repositorio.
- **`AuditLogResponse` y la exportación CSV de auditoría (ENG-062)** actualizados con las tres columnas nuevas (`ip`, `correlation_id`, `outcome`) para no dejar el nuevo esquema invisible desde la API/exportación existente.
- **Corrección de tipo evitada**: se consideró pasar un actor literal `'cli'` para el comando de consola de asignación de roles, pero `audit_logs.user_id` es una columna `uuid` nativa en PostgreSQL — un string no-UUID habría fallado en producción (aunque no en las pruebas, que usan SQLite). Se optó por `actorId` nullable en su lugar, dejando `userId` en `null` para asignaciones disparadas por consola.

### Validaciones

- Suites dirigidas de `Modules\Audit`, `Modules\Admin`, `Modules\Identity` y `Modules\Authorization` ✅ — `149 passed` en conjunto (incluye 3 pruebas nuevas de auditoría de autenticación, 1 de asignación de roles, 1 de cambio de configuración, y 2 unitarias de enriquecimiento de IP/Correlation ID en `DatabaseAuditLogger`).
- Pint ✅ y PHPStan nivel 8 ✅ (con `--memory-limit=512M`, el límite por defecto de 128M no alcanza para el análisis completo del repositorio) sin errores sobre todos los módulos, y también sobre el repositorio completo.
- Se descubrió y corrigió un supuesto propio durante la escritura de pruebas: `Modules\Identity\Domain\Exceptions\InvalidCredentials` extiende `RuntimeException` (no `DomainException`), por lo que un login fallido responde 500, no 401 — comportamiento preexistente fuera de alcance de esta historia; la prueba nueva se ajustó para reflejar el comportamiento real en lugar de asumir un 401.

**Estado:** Finalizado.

## 2026-08-28 — IMP-069 (Cierre de ENG-069 — Gestión de secretos)

### Alcance acordado con el usuario

Tercera historia de la Fase 14 — Seguridad y cumplimiento. Investigación previa: todos los secretos ya se leían vía `env()` en `config/*.php` (ningún secreto hardcodeado en el código, verificado por búsqueda explícita). "Rotación" y "Llaves de integraciones" resultaron ser bullets ya resueltos por el mecanismo de rotación de llaves de simuladores (`RotateSimulatorIntegrationKeyHandler`) construido en el vecindario de ENG-067 — hash SHA-256, revelado único, ciclo de vida completo — y no había ninguna otra integración externa activa en el sistema (Postmark/Resend/Slack en `config/services.php` son *stubs* sin consumidor). El hueco real de "Rotación" estaba en Sanctum: `config/sanctum.php` tenía `'expiration' => null`, los tokens de acceso nunca expiraban. No existía ninguna validación de variables de entorno requeridas al arrancar, ni ningún mecanismo de escaneo de secretos en Git — cero CI en todo el repositorio. Alcance acordado: expiración de tokens Sanctum, validación de variables requeridas en producción, y un resguardo ligero de escaneo de secretos en Git — sin pipeline de CI completo ni gestor de secretos externo (Vault/AWS Secrets Manager). Detalle completo en `docs/plans/2026-08-28-gestion-secretos-eng069-design.md`.

### Completado

- **Expiración de tokens Sanctum**: `config/sanctum.php` cambia de `'expiration' => null` a `env('SANCTUM_EXPIRATION_MINUTES', 43200)` (30 días por defecto, mismo patrón `env()` ya usado en ese archivo). No requirió ningún cambio de código en `SanctumAccessTokenIssuer` ni en ningún caso de uso — Sanctum calcula la expiración internamente a partir de `created_at + config('sanctum.expiration')`. Se eligió un valor generoso porque el sistema no tiene ningún mecanismo de *refresh token*.
- **Validación de variables de entorno requeridas al arrancar**: nueva clase pura `Modules\Foundation\Infrastructure\Environment\RequiredSecretsValidator::ensureAllPresent(array $values)`, que lanza `MissingRequiredSecrets` listando las variables faltantes. Cableada en `FoundationServiceProvider::boot()`, activa solo cuando `app()->environment('production')`, verificando `APP_KEY`, la contraseña de la conexión de base de datos activa, y las credenciales S3 (`Modules\FileStorage` usa el disco `s3` de forma incondicional, así que son requeridas en producción sin importar `FILESYSTEM_DISK`). Se lee siempre vía `config()`, nunca `env()` directamente, para seguir funcionando correctamente con `config:cache` activo en producción.
- **Resguardo ligero de escaneo de secretos en Git**: `Modules\Foundation\Infrastructure\Security\SecretPatternScanner::scan(string $line): list<string>` detecta un conjunto acotado de patrones de alta confianza (AWS Access Key ID, AWS Secret Access Key, bloques de llave privada PEM, *webhooks* de Slack) — deliberadamente no intenta detectar "cualquier contraseña" de forma genérica para evitar falsos positivos. Comando Artisan `secrets:scan` (`Modules\Foundation\Presentation\Console\ScanForSecretsCommand`) lee por STDIN y reporta coincidencias con su número de línea, saliendo con código 1 si hay alguna. Hook de Git en `.githooks/pre-commit` (shell, sin dependencias de PHP en su lógica de detección) que escanea el diff en stage vía `git diff --cached` canalizado al comando a través de `docker compose exec -T app`. No se activa automáticamente — se documenta cómo activarlo con `git config core.hooksPath .githooks`.

### Validaciones

- Suites completas de `Modules\Foundation`, `Modules\Identity` y `Modules\Authorization` ✅ — `115 passed (332 assertions)` en conjunto (incluye 2 pruebas nuevas de expiración de tokens, 3 de `RequiredSecretsValidator`, 6 de `SecretPatternScanner`, 2 de `ScanForSecretsCommand`).
- Verificación manual end-to-end del comando `secrets:scan` vía `docker compose exec -T app php artisan secrets:scan` con contenido real por STDIN (detecta y bloquea con código 1; contenido limpio sale con código 0) — su lectura de STDIN no se cubre con Pest (se extrajo la lógica de detección de líneas a un método público testeable por separado, `findViolations()`, siguiendo el mismo criterio ya usado para no probar por integración el `if` de ambiente en el `ServiceProvider`).
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre todos los archivos nuevos/modificados de esta historia.

**Estado:** Finalizado.

## 2026-08-28 — IMP-070 (Cierre de ENG-070 — Protección de datos personales)

### Alcance acordado con el usuario

Cuarta historia de la Fase 14 — Seguridad y cumplimiento, y la primera de esta fase con **alcance completo** (todas las anteriores optaron por el reducido). Investigación previa: "Minimización" ya estaba satisfecha (ningún módulo recolecta más de lo necesario). "Consentimiento" tenía un precedente angosto (booleano de `Modules\Notification`, sin versión de política). Los huecos reales: sin flujo de eliminación de cuenta, sin retención, sin exportación de autoservicio. Alcance confirmado: borrado físico real (no anonimización en el lugar), consentimiento versionado por política construido ahora (no diferido a ENG-071), retención con 3 años de inactividad configurable, y exportación granular a través de todos los módulos con datos del usuario. Certificados: se conservan indefinidamente, desvinculados del usuario eliminado. Detalle completo en `docs/plans/2026-08-28-proteccion-datos-personales-eng070-design.md`.

### Completado

- **Correcciones de esquema para el borrado físico**: `authorization_role_assignments.user_id` gana FK con `cascadeOnDelete()` (no tenía ninguna); `audit_logs.user_id` gana FK con `nullOnDelete()` (tampoco tenía ninguna, pese a ser nullable desde ENG-068); `certificates.user_id` cambia de `cascadeOnDelete()` a `nullOnDelete()` con la columna vuelta nullable — decisión confirmada con el usuario para que un certificado siga siendo verificable públicamente por su código aunque la cuenta del titular se elimine.
- **Refactor de nullable `userId` en `Modules\Certification`**: `Certificate::userId` pasa de `string` a `?string` (constructor, `restore()`, accesor); `CertificateResponse`/`CertificateVerificationResponse` exponen el campo como nullable; `EloquentCertificateRepository::toDomain()` corrige un bug latente donde `(string) null` habría producido `''` en vez de `null`; `VerifyCertificateHandler` deja de asumir que el titular siempre existe (`holderName` nullable cuando el usuario fue eliminado).
- **Eliminación de cuenta (autoservicio)**: `DELETE /api/v1/auth/me` → `Modules\Identity\Application\UseCases\DeleteAccountUseCase`, que registra `identity.account_deleted` en auditoría (con `actor_id` duplicado en `metadata` porque el mismo borrado nulifica la columna `user_id` de esa misma entrada vía la FK recién agregada) antes de borrar físicamente la fila `users`. Nuevos métodos en `UserRepository`: `delete()` y `findInactiveBefore()`.
- **Retención**: `identity:purge-inactive-accounts` (programado diariamente en `routes/console.php`), reutiliza el mismo `DeleteAccountUseCase` para cuentas con `last_login_at` (o `created_at` si nunca inició sesión) anterior a `IDENTITY_RETENTION_INACTIVITY_YEARS` años (default 3, `config/identity.php`).
- **Nuevo módulo `Modules\Legal`**: agregados `ConsentPolicy` (versionado por clave, cada publicación crea una nueva fila en vez de sobrescribir) y `UserConsent` (usuario + política + versión aceptada + fecha). Rutas: `GET /api/v1/legal/policies` (pública), `POST /api/v1/legal/policies` (permiso nuevo `legal_policies.manage`, solo SuperAdmin), `POST /api/v1/legal/consents` y `GET /api/v1/legal/me/consents` (autenticado). Deliberadamente genérico, no específico de menores de edad, para que ENG-071 lo extienda en vez de construir un segundo mecanismo.
- **Exportación de datos personales**: `GET /api/v1/auth/me/data-export` → `Modules\Identity\Application\UseCases\ExportMyDataUseCase`, que agrega directamente (vía las interfaces de repositorio de cada módulo, no vía `QueryBus`, simplificación deliberada frente al diseño original que habría requerido ~40 archivos nuevos de Query/Handler/Response para un caso de uso puramente de lectura) datos de: Identity (perfil), Authorization (asignaciones de rol), Legal (consentimientos), Academic (inscripciones, intentos de examen), Certification (certificados), Simulation (sesiones, con eventos de telemetría y puntos de decisión anidados por sesión — no las muestras de telemetría continua de alta frecuencia, para no generar una respuesta desproporcionadamente grande), RoadPassport, Notification (notificaciones y preferencias), Gamification (insignias, logros, participaciones en retos, experiencia) y Learning (eventos de aprendizaje, resueltos a través de las inscripciones del usuario).

### Validaciones

- Suites completas de `Modules\Identity`, `Modules\Authorization`, `Modules\Certification`, `Modules\Legal` ✅ — `225 passed` en conjunto (incluye 3 nuevas de eliminación de cuenta, 3 de retención, 3 de exportación, 13 de `Modules\Legal`, más ajustes a fixtures preexistentes que asumían usuarios no persistidos y que las nuevas FK dejaron de aceptar).
- Suites completas de `Modules\Simulation`, `Modules\Gamification`, `Modules\Notification`, `Modules\RoadPassport`, `Modules\Learning`, `Modules\Admin`, `Modules\Audit` ✅ — `593 passed` en conjunto (verifican que las dependencias directas del agregador de exportación no rompieron nada en esos módulos).
- Suite completa de `Modules\Academic` — `874 passed, 1 failed`; el único fallo (`GradingResultTest`, un `TypeError` no envuelto en `InvalidArgumentException` al pasar un array con tipos inválidos) es preexistente y no relacionado con esta historia (no se tocó ningún archivo de `Domain\ValueObjects` de calificación) — verificado leyendo el test y confirmando que ningún cambio de ENG-070 lo toca.
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre el repositorio completo.
- Se descubrió y corrigió durante la escritura de pruebas: el guard de Sanctum (`Illuminate\Auth\RequestGuard`) memoiza el usuario autenticado por instancia, que persiste entre múltiples peticiones dentro de un mismo método de prueba — una prueba que verificaba que un token quedara invalidado tras eliminar la cuenta necesitó `Auth::forgetGuards()` entre ambas peticiones para forzar una resolución nueva.

**Estado:** Finalizado.

## 2026-08-28 — IMP-071 (Cierre de ENG-071 — Seguridad para menores de edad)

### Alcance acordado con el usuario

Quinta y última historia planificada de la Fase 14 — Seguridad y cumplimiento (queda ENG-072 — Idempotencia, aún dentro de la fase). Investigación previa encontró un bloqueo real: no existía ningún campo de fecha de nacimiento ni edad en ningún módulo (`Modules\Identity`, `Modules\Academic`, `Modules\Organization`) — sin ese dato, "menor de edad" no tenía ninguna condición que lo disparara. Se confirmó una sola fuga de datos personales (`GET /api/v1/certification/verify/{code}` exponía el nombre completo del titular sin autenticación) y que "protección de perfiles" ya estaba satisfecha (ningún leaderboard ni perfil público existe en `Modules\Gamification`). Hallazgo adyacente no accionado: `InstitutionalAdmin` puede gestionar cualquier usuario del sistema sin límite de organización (`ListUsersUseCase`/`DeactivateUserUseCase` sin scoping) — problema de autorización general, no específico de menores, dejado fuera de esta historia. Alcance acordado: fecha de nacimiento opcional, consentimiento parental autodeclarado (sin verificar la identidad real de un tutor), corrección de la fuga confirmada, y consulta institucional de consentimiento parental por organización. Detalle completo en `docs/plans/2026-08-28-seguridad-menores-eng071-design.md`.

### Completado

- **Detección de minoría de edad**: `User` gana `?DateTimeImmutable $dateOfBirth` (nullable, opcional) y el método de dominio `isMinor(?DateTimeImmutable $asOf = null): bool` (menor de 18 años; `false` cuando no hay fecha registrada — limitación conocida y documentada, no un error silencioso). Solo `POST /api/v1/auth/register` gana el campo opcional `date_of_birth` — la importación masiva de usuarios (ENG-061) y otros flujos administrativos de creación de cuentas quedan fuera de esta historia.
- **Consentimiento parental autodeclarado**: `Modules\Legal`'s `UserConsent` gana `?string $guardianDeclaration`. `RecordConsentHandler` pasa a depender de `Modules\Identity`'s `UserRepository`; si el usuario que acepta es menor según su fecha de nacimiento *actual*, exige `guardian_declaration` en la petición (nueva excepción `GuardianDeclarationRequired`, 422) — no se modela un tutor como entidad propia con cuenta o verificación.
- **Corrección de la fuga confirmada**: `VerifyCertificateHandler` suprime `holderName` (ya nullable desde ENG-070) cuando el titular es menor *hoy*, no en la fecha de emisión del certificado — protege a la persona mientras siga siendo menor. La respuesta pública ya no distingue "el titular es menor" de "el titular eliminó su cuenta" (ENG-070); ambos casos simplemente omiten el nombre.
- **Controles institucionales**: nuevo permiso `organization_consents.view` (SuperAdmin + InstitutionalAdmin). Nueva consulta en `Modules\Legal` (`GetOrganizationMinorsConsentsQuery`/`Handler`) que, dado un `organizationId`, resuelve los usuarios inscritos vía `Modules\Academic`'s `EnrollmentRepository::all(organizationId:)` (dependencia cruzada de solo lectura), filtra a los menores, y devuelve su historial de consentimiento — un adulto inscrito no aparece en este listado. Ruta: `GET /api/v1/legal/organizations/{organizationId}/minors-consents`.

### Validaciones

- Suites completas de `Modules\Identity`, `Modules\Legal`, `Modules\Certification`, `Modules\Authorization` y `tests/Unit/UserTest.php` ✅ — `253 passed` en conjunto (incluye 3 nuevas de registro con fecha de nacimiento, 4 de `isMinor()`, 4 de exigencia/registro de declaración de tutor a nivel unitario y HTTP, 1 de supresión de nombre para menores en verificación pública, 4 de consulta institucional de consentimientos por organización).
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre el repositorio completo.
- Se descubrió y corrigió durante la escritura de pruebas: `Enrollment::assertValid()` rechaza una inscripción con `organizationId` no nulo a menos que `source` sea explícitamente `EnrollmentSource::Institutional` (el valor por defecto es `Individual`) — los nuevos fixtures de prueba que simulaban inscripciones institucionales no lo especificaban.

**Estado:** Finalizado.

## 2026-08-28 — IMP-072 (Cierre de ENG-072 — Idempotencia)

### Alcance acordado con el usuario

Sexta y última historia de la Fase 14 — Seguridad y cumplimiento (con este cierre, la fase completa queda terminada; Fase 15 — Integraciones empieza en ENG-073). Investigación previa confirmó que "Registro de simulaciones" y "Sincronizaciones móviles" ya estaban resueltos desde ENG-050 — `Modules\Simulation`'s endpoints de telemetría/decisiones reciben un `id` generado por el cliente por cada muestra/evento, y los repositorios usan `insertOrIgnore()` sobre ese `id`; reenviar el mismo lote es un no-op silencioso. "Pagos" no tiene código que corregir porque el módulo no existe (es ENG-077, historia futura de Fase 15+). Huecos reales encontrados: `CreateEnrollmentHandler`/`CreateInstitutionalEnrollmentHandler` (Academic) e `IssueCertificateHandler` (Certification) verifican duplicados pero lanzan una excepción 409 en vez de devolver el recurso existente; `AssignRoleHandler` (Authorization) no tenía ninguna verificación de existencia — reintentar creaba filas duplicadas de `RoleAssignment` silenciosamente. Alcance acordado: corregir los tres huecos a nivel de aplicación devolviendo el recurso existente en vez de fallar. Detalle completo en `docs/plans/2026-08-28-idempotencia-eng072-design.md`.

### Completado

- **`CreateEnrollmentHandler` y `CreateInstitutionalEnrollmentHandler`** (`Modules\Academic`): cuando `findActiveOrPendingFor()` encuentra una inscripción activa/pendiente ya existente, se devuelve esa inscripción (`EnrollmentResponse::fromEnrollment($existing)`) en vez de lanzar `EnrollmentAlreadyExists` — la excepción quedó sin ningún llamador y se eliminó. `CreateBulkEnrollmentsHandler` no se modificó: su reporte de fila duplicada (`'created': false, 'error_code': 'ENROLLMENT_ALREADY_EXISTS'`) es un patrón de importación masiva ya establecido (ENG-061), no el mismo problema de reintento de una sola petición.
- **`IssueCertificateHandler`** (`Modules\Certification`): mismo cambio — cuando `findByUserAndCourse()` encuentra un certificado existente, se devuelve ese certificado en vez de lanzar `CertificateAlreadyExists` (eliminada, sin llamadores). La verificación pública de certificados y el resto del módulo no cambian.
- **`AssignRoleHandler`** (`Modules\Authorization`): gana un método `findExisting()` que busca, entre las asignaciones del usuario objetivo (`RoleAssignmentRepository::findByUserId()`), una con el mismo rol y organización antes de crear una nueva. Si existe, se devuelve sin crear una fila duplicada **ni registrar una segunda entrada de auditoría falsa** — `authorization.role_assigned` solo se registra cuando efectivamente se crea una asignación nueva.

### Validaciones

- Suites de `Modules\Certification` y `Modules\Authorization` completas ✅ — `125 passed` en conjunto (incluye las pruebas de idempotencia reescritas para certificados y asignación de roles).
- Pruebas dirigidas de `Modules\Academic` (`EnrollmentTest.php`, `EnrollmentHandlerTest.php`, las únicas afectadas por este cambio) ✅ — `23 passed` (se evitó ejecutar la suite completa de `Modules\Academic`, que excede el límite de memoria por defecto de PHPStan/Pest en este entorno de forma independiente a este cambio — ya documentado en historias previas de esta sesión).
- Pint ✅ y PHPStan nivel 8 ✅ sin errores sobre el repositorio completo.

Con esto cierra por completo la **Fase 14 — Seguridad y cumplimiento** (ENG-067 a ENG-072).

**Estado:** Finalizado.

## 2026-08-29 — IMP-073 (Cierre de ENG-073 — API Keys para sistemas externos)

### Alcance acordado con el usuario

Primera historia de la Fase 15 — Integraciones. No existía ningún mecanismo de API key para consumidores externos (sistemas de terceros); el bounded context más cercano era la llave de integración de simuladores construida en `Modules\Simulation` (ENG-067). Alcance acordado: construir un módulo nuevo `Modules\Integration` clonando deliberadamente el patrón de llave de integración de Simulation (SHA-256, revelado único en texto plano, `fromHash()` para reconstitución) — sin extraerlo a un kernel compartido, por tratarse de dos bounded contexts DDD independientes y ser una clase pequeña (~20 líneas) — y añadiéndole las dos capacidades que Simulation nunca necesitó: alcances (scopes) y expiración. No se retrofiteó control de alcances a la superficie de API existente: se construyeron dos endpoints de humo nuevos para probar el mecanismo de punta a punta, dejando la decisión de qué endpoints existentes exponer a consumidores externos para ENG-076 (Integraciones institucionales). Detalle completo en `docs/plans/2026-08-29-api-keys-sistemas-externos-eng073-design.md`.

### Completado

- **`Modules\Integration` (módulo nuevo)** — agregado `ApiConsumer` (identificación, `scopes` como `list<string>`, `IntegrationKey`, expiración opcional, ciclo de vida `Active|Suspended|Revoked` con `suspend()`/`reactivate()`/`revoke()` terminal vía `InvalidApiConsumerTransition` uniforme, `rotateIntegrationKey()`, `isUsableAt()`, historial append-only `ApiConsumerHistoryEntry`); `ApiConsumerRepository` + `EloquentApiConsumerRepository` (mismo patrón transaccional de Simulation: `updateOrCreate` más borrado-y-recreación del historial); migración `integration_api_consumers` (llave `integration_key_hash` única) + `integration_api_consumer_history_entries` (FK cascada).
- **Application** — cinco Commands (`RegisterApiConsumerCommand` valida cada scope contra `Modules\Authorization`'s `Permission::tryFrom()`, lanzando `InvalidApiConsumerScope` si no es un permiso válido) + dos Queries + siete handlers; cada acción administrativa (registrar/suspender/reactivar/revocar/rotar) audita vía `Modules\Audit`'s `AuditLogger` con `userId` del administrador que la realiza — deliberadamente **no** se audita cada autenticación de request de un consumidor, solo los cambios de ciclo de vida.
- **Autenticación y autorización externa** — middleware `AuthenticateApiConsumer` (alias `api_consumer.auth`: hashea el bearer token, busca por hash, rechaza si no está `isUsableAt(now)`, adjunta `authenticated_api_consumer_id`/`authenticated_api_consumer_scopes` como atributos de request) y `EnsureApiConsumerScope` (alias `scope:*`, parametrizado con el scope requerido, 403 si no está presente). Limitador de tasa nombrado `external-integration` (60/min, por `authenticated_api_consumer_id` con fallback a IP) registrado en `FoundationServiceProvider`.
- **Presentation** — `ApiConsumerController` (CRUD administrativo completo bajo `auth:sanctum` + permisos nuevos `api_consumers.manage`/`api_consumers.view`, otorgados únicamente a `SuperAdmin`) y dos endpoints de humo (`GET /api/v1/external/status`, `GET /api/v1/external/reports/ping` gateado con `scope:reports.view`) bajo `api_consumer.auth` + `throttle:external-integration`.
- **`Modules\Authorization`** — `Permission::ManageApiConsumers`/`ViewApiConsumers` nuevos, otorgados solo a `SuperAdmin` en `RolePermissions` (mismo patrón que `legal_policies.manage`).
- Registro en `bootstrap/providers.php` (`IntegrationServiceProvider`) y `bootstrap/app.php` (alias `api_consumer.auth`, `scope`).

### Validaciones

- Suite completa de `Modules\Integration` (dominio, aplicación, integración de persistencia, middlewares, feature, rate limiting) ✅ — `55 passed`.
- `RolePermissionsTest` (`Modules\Authorization`) actualizado con el caso nuevo ✅ — `96 passed` en conjunto con la suite de Integration.
- Pint ✅ sobre `modules/Integration`, `modules/Authorization` y `bootstrap` (los directorios tocados por esta historia). Un `pint --test` sobre el repositorio completo encontró un único problema de estilo preexistente y no relacionado en `modules/Learning/Tests/Unit/Application/GetEnrollmentLearningEventsHandlerTest.php` (confirmado sin cambios locales via `git status`) — fuera de alcance de esta historia, no corregido aquí.
- PHPStan nivel 8 ✅ sin errores sobre el repositorio completo.

**Estado:** Finalizado.

## 2026-08-29 — IMP-074 (Cierre de ENG-074 — Webhooks)

### Alcance acordado con el usuario

Segunda historia de la Fase 15 — Integraciones. Investigación previa confirmó que esta historia era enteramente nueva: cero mecanismo de eventos de dominio (sin `Illuminate\Events\Dispatcher`, sin directorios `Domain\Events`, sin concepto de suscripción a webhook en ningún módulo), cero cliente HTTP saliente en todo el repositorio, y la cola de Laravel configurada (`config/queue.php`, driver `database`, tablas migradas) pero nunca usada realmente (cero `Job`/`ShouldQueue`). El patrón más cercano reutilizable, `Modules\Integration`'s `ApiConsumer`/`IntegrationKey` (ENG-073), resultó **no ser directamente reutilizable** para el secreto de firma: `IntegrationKey` solo guarda un hash SHA-256 irreversible (autenticación por comparación), mientras que un secreto de webhook debe poder recuperarse en cada entrega para calcular el HMAC saliente — por eso se cifra reversiblemente con `Crypt`, no se hashea. Alcance acordado: construir el mecanismo completo de webhooks (suscripciones administradas, firma, entrega asíncrona vía cola real, reintentos, registro de entregas, dead-letter) pero cableando solo dos eventos de dominio reales (`enrollment.created`, `certificate.issued`) como prueba de punta a punta, sin retrofitear un bus de eventos genérico a través de todos los módulos. Detalle completo en `docs/plans/2026-08-29-webhooks-eng074-design.md`.

### Completado

- **`Modules\Webhook` (módulo nuevo)** — `WebhookSubscription` (url, `WebhookSigningSecret`, lista de `WebhookEventName` suscritos validados contra un enum cerrado a los dos eventos del alcance reducido, ciclo de vida `Active/Suspended` sin borrado) y `WebhookDelivery` (una fila por entrega: `WebhookDeliveryStatus` `Pending/Delivered/Failed/DeadLettered`, contador de intentos, última respuesta truncada a 1000 caracteres, próxima fecha de reintento con backoff `30s·2^(intentos-1)` con techo de 1 hora, dead-letter tras 5 intentos); repositorios Eloquent (`EloquentWebhookSubscriptionRepository` cifra/descifra el secreto con `Crypt::encryptString()`/`decryptString()` — el dominio solo conoce el valor en texto plano).
- **Application** — cinco Commands + tres Queries + ocho handlers; `RegisterWebhookSubscriptionHandler` valida cada evento contra `WebhookEventName::tryFrom()` (`InvalidWebhookEventName` si no es válido); acciones administrativas (crear/suspender/reactivar/rotar secreto) auditadas con el `userId` del administrador; `RetryWebhookDeliveryHandler` reencola una entrega `Failed`/`DeadLettered` de inmediato sin esperar el backoff.
- **Entrega real** — `DeliverWebhookJob` (primer `ShouldQueue` de todo el repositorio) firma el payload exacto con HMAC-SHA256 (`X-Webhook-Signature: sha256=...`), envía `X-Webhook-Delivery-Id` (estable entre reintentos, para deduplicación del lado del receptor) y `X-Webhook-Event`; toda la lógica de reintento/backoff/dead-letter vive en el propio `WebhookDelivery` (dominio), no en `$tries`/`backoff()` de Laravel — el job se re-despacha manualmente vía `WebhookDeliveryDispatcher` (puerto de Aplicación, implementado en Infraestructura) para mantener el estado de reintento visible y consultable vía API en vez de escondido en `failed_jobs`.
- **Presentation** — `WebhookSubscriptionController` (CRUD administrativo bajo `auth:sanctum` + permisos nuevos `webhooks.manage`/`webhooks.view`, `SuperAdmin` únicamente) y `WebhookDeliveryController` (listado filtrable por estado, reintento manual).
- **Eventos cableados**: `Modules\Academic`'s `CreateEnrollmentHandler` (y `CreateBulkEnrollmentsHandler`, que lo envuelve) publica `enrollment.created` tras guardar; `Modules\Certification`'s `IssueCertificateHandler` publica `certificate.issued` tras guardar — ambos solo en el camino de creación real, no cuando el handler devuelve un recurso existente por idempotencia (ENG-072). **Diferido explícitamente**: `CreateInstitutionalEnrollmentHandler` no está cableado (no reutiliza `CreateEnrollmentHandler` internamente) — matriculaciones institucionales no disparan el webhook por ahora, una limitación conocida y documentada, no un olvido.
- **Payload "delgado"**: cada evento envía solo identificadores (`{event, occurred_at, data: {...ids...}}`), no el recurso completo — decisión deliberada para que el receptor vuelva a consultar la API por el estado actual.

### Riesgo operativo identificado (no corregido en esta historia)

`compose.yaml` no tiene ningún worker de cola corriendo (`php artisan queue:work`) — un `Job` despachado con el driver `database` de producción quedaría en la tabla `jobs` sin procesarse hasta que exista un worker. Esto ya se había identificado como gap en `docs/plans/2026-08-27-exportaciones-eng062-design.md` y sigue sin resolverse; queda fuera del alcance de código de esta historia (es una decisión de infraestructura/despliegue), pero se documenta aquí explícitamente porque ENG-074 es la primera historia cuya funcionalidad de negocio (entrega de webhooks) depende de que ese worker exista y esté corriendo.

### Validaciones

- Suite completa de `Modules\Webhook` ✅ — 21 dominio + 34 aplicación + 6 integración de persistencia + 19 feature (incluyendo entrega HTTP real con `Http::fake()`, verificación de firma HMAC, reintentos con backoff hasta dead-letter, recuperación manual, filtrado de entregas) = `80 passed` en total.
- `RolePermissionsTest` actualizado con el caso nuevo ✅.
- Pruebas dirigidas de `Modules\Academic` y `Modules\Certification` afectadas por el cableado de eventos (`EnrollmentHandlerTest`, `BulkEnrollmentHandlerTest`, `CertificateHandlerTest` a nivel unitario; `EnrollmentTest`, `EnrollmentProgressTest`, `CertificateTest`, `CertificateVerificationTest` a nivel feature/HTTP) ✅ — `57 passed` a nivel feature, confirmando que el cableado no rompe ningún flujo existente. Se evitó ejecutar la suite completa de `Modules\Academic` (excede el límite de memoria por defecto de Pest en este entorno, de forma independiente a este cambio — ya documentado en historias previas de esta sesión).
- Pint ✅ sobre los directorios tocados por esta historia; un `pint --test` sobre el repositorio completo encontró el mismo problema de estilo preexistente y no relacionado en `modules/Learning` ya señalado en el cierre de ENG-073 — sigue fuera de alcance.
- PHPStan nivel 8 ✅ sin errores sobre el repositorio completo.

**Estado:** Finalizado.
