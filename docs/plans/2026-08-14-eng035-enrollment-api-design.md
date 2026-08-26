# ENG-035 - Enrollment HTTP API and Permissions (Design)

## Goal

Cerrar la exposicion HTTP de enrollments para ENG-035 junto con la capa minima de permisos necesaria para operar altas, consultas y transiciones de estado de forma consistente con el resto del modulo Academic.

## Context

ENG-035 ya tiene dominio, persistencia, CQRS base, lifecycle y operaciones bulk e institutional resueltas. Falta conectar esas capacidades al borde HTTP y alinearlas con el patron de autorizacion existente en `modules/Academic/Presentation/Routes/api.php`.

El sistema ya cuenta con:

- `CommandBus` y `QueryBus`
- handlers de create/list/get/activate/complete/cancel
- respuestas de aplicacion (`EnrollmentResponse`, `EnrollmentListItemResponse`, `BulkEnrollmentResponse`)
- renderizado uniforme de errores de dominio en `bootstrap/app.php`
- middleware `permission:...` para proteccion por rol

La decision de diseno es mantener toda la logica de negocio en application/domain y limitar HTTP a validacion de forma, autorizacion y mapeo request/response.

## Approaches Considered

### Option 1 - Single controller plus FormRequests (recommended)

Un solo `EnrollmentController` expone todas las rutas y cada operacion usa su propio `FormRequest` cuando recibe payload.

Ventajas:

- sigue el patron ya presente en controladores de Academic
- reduce dispersion de endpoints relacionados
- deja CQRS en la capa de aplicacion, no en la forma del controlador
- facilita tests feature por caso de uso y por permiso

Desventaja:

- el controlador concentra varias acciones, aunque todas pertenecen al mismo agregado

### Option 2 - Separate query and command controllers

Separar endpoints de consulta y mutacion en controladores distintos.

Ventajas:

- representa CQRS mas explicitamente en HTTP
- reduce el tamano de cada controlador

Desventajas:

- agrega complejidad estructural sin una ganancia clara para este modulo
- se aleja del estilo actual del repositorio

### Option 3 - Single controller with inline validation

Usar un solo controlador y validar todo dentro de los metodos.

Ventajas:

- menor cantidad inicial de clases

Desventajas:

- empeora legibilidad y reuso
- hace mas fragil el manejo de errores 422
- rompe el patron actual del proyecto

## Chosen Design

Se adopta la opcion 1: `EnrollmentController` unico con `FormRequest` por operacion y permisos agrupados en rutas.

## HTTP Surface

Base path: `/api/v1/academic`

### Endpoints

- `POST /enrollments`
- `POST /enrollments/bulk`
- `POST /enrollments/institutional`
- `GET /enrollments`
- `GET /enrollments/{enrollmentId}`
- `POST /enrollments/{enrollmentId}/activate`
- `POST /enrollments/{enrollmentId}/complete`
- `POST /enrollments/{enrollmentId}/cancel`

### Route protection

- `auth:sanctum` para toda la superficie de enrollments
- `permission:enrollments.view` para `GET /enrollments` y `GET /enrollments/{enrollmentId}`
- `permission:enrollments.manage` para creates y transiciones

## Request Contracts

### Create individual enrollment

`POST /api/v1/academic/enrollments`

Payload:

```json
{
  "course_id": "uuid",
  "user_id": "uuid",
  "status": "pending",
  "starts_at": "2026-09-01T00:00:00Z",
  "ends_at": "2026-12-01T00:00:00Z"
}
```

Reglas:

- `course_id` requerido, uuid
- `user_id` requerido, uuid
- `status` requerido, uno de `pending|active|completed|canceled`
- `starts_at` opcional, fecha valida
- `ends_at` opcional, fecha valida e igual o posterior a `starts_at`
- `source` no llega por request; HTTP fija `individual`

Respuesta exitosa: `201` con `data` desde `EnrollmentResponse::toArray()`.

### Create bulk enrollments

`POST /api/v1/academic/enrollments/bulk`

Payload:

```json
{
  "course_id": "uuid",
  "user_ids": ["uuid-1", "uuid-2"],
  "status": "active",
  "starts_at": null,
  "ends_at": null
}
```

Reglas:

- `course_id` requerido, uuid
- `user_ids` requerido, array, minimo 1
- cada elemento de `user_ids` debe ser uuid
- `status`, `starts_at`, `ends_at` siguen las mismas reglas del create individual
- `source` no llega por request; HTTP fija `bulk`

Respuesta exitosa: `201` con `data` desde `BulkEnrollmentResponse::toArray()`.

### Create institutional enrollment

`POST /api/v1/academic/enrollments/institutional`

Payload:

```json
{
  "course_id": "uuid",
  "user_id": "uuid",
  "organization_id": "uuid",
  "status": "active",
  "starts_at": null,
  "ends_at": null
}
```

Reglas:

- `course_id` requerido, uuid
- `user_id` requerido, uuid
- `organization_id` requerido, uuid
- `status`, `starts_at`, `ends_at` siguen las mismas reglas del create individual
- `source` no llega por request; HTTP fija `institutional`

Respuesta exitosa: `201` con `data` desde `EnrollmentResponse::toArray()`.

### List enrollments

`GET /api/v1/academic/enrollments`

Filtros opcionales:

- `course_id`
- `user_id`
- `organization_id`
- `status`
- `source`

Reglas:

- ids como uuid cuando se proveen
- `status` restringido a `pending|active|completed|canceled`
- `source` restringido a `individual|bulk|institutional`

Respuesta exitosa: `200` con `data` como lista de `EnrollmentListItemResponse::toArray()`.

### Show enrollment

`GET /api/v1/academic/enrollments/{enrollmentId}`

Reglas:

- `enrollmentId` en ruta debe ser uuid

Respuesta exitosa: `200` con `data` desde `EnrollmentResponse::toArray()`.

### Lifecycle transitions

- `POST /api/v1/academic/enrollments/{enrollmentId}/activate`
- `POST /api/v1/academic/enrollments/{enrollmentId}/complete`
- `POST /api/v1/academic/enrollments/{enrollmentId}/cancel`

Reglas:

- sin body
- `enrollmentId` en ruta debe ser uuid

Respuesta exitosa: `200` con `data` desde `EnrollmentResponse::toArray()`.

## Controller and Request Layout

### Controller

Crear `modules/Academic/Presentation/Http/Controllers/EnrollmentController.php`.

Responsabilidades:

- recibir request validada
- mapear payload a command/query
- despachar a `CommandBus` o `QueryBus`
- transformar respuesta de aplicacion a JSON HTTP

No debe:

- contener reglas de negocio
- capturar excepciones de dominio para reinterpretarlas localmente
- recalcular permisos fuera del middleware de rutas

### Form requests

Crear:

- `CreateEnrollmentRequest.php`
- `CreateBulkEnrollmentsRequest.php`
- `CreateInstitutionalEnrollmentRequest.php`
- `ListEnrollmentsRequest.php`

Las transiciones no requieren `FormRequest` porque no reciben payload; el `whereUuid()` de la ruta cubre el parametro y la semantica de negocio queda en los handlers.

## Authorization Design

Agregar permisos al enum `Permission`:

- `enrollments.view`
- `enrollments.manage`

Mapeo inicial en `RolePermissions`:

- `SuperAdmin`: view + manage
- `InstitutionalAdmin`: view + manage
- `Teacher`: view
- `Student`: ninguno

Esta decision sigue el criterio ya aprobado para ENG-035 y evita dejar una API nueva desprotegida o con reglas ad hoc.

## Error Handling

No se agrega una capa especial de excepciones en controller. Se reutiliza el pipeline actual de `bootstrap/app.php`.

Resultados esperados:

- `401` -> usuario no autenticado
- `403` -> usuario autenticado sin permiso suficiente
- `404` -> curso o enrollment inexistente
- `409` -> enrollment duplicado o transicion invalida de negocio
- `422` -> payload invalido

Errores de negocio relevantes:

- `CourseNotFound`
- `EnrollmentNotFound`
- `EnrollmentAlreadyExists`
- `InvalidEnrollment`

## Data Flow

### Create individual

1. request valida forma del payload
2. controller crea `CreateEnrollmentCommand`
3. `CreateEnrollmentHandler` valida curso existente y duplicado activo/pending
4. handler crea aggregate `Enrollment`, persiste y devuelve `EnrollmentResponse`
5. controller responde `201`

### Create bulk

1. request valida forma del payload
2. controller crea `CreateBulkEnrollmentsCommand`
3. handler procesa cada `user_id`
4. devuelve resumen total y resultados por usuario
5. controller responde `201`

### Queries and transitions

1. middleware resuelve autenticacion y permiso
2. controller despacha query o command correspondiente
3. handler devuelve response o lanza excepcion de dominio
4. exception renderer produce JSON estandar si corresponde

## Testing Strategy

### Feature tests

Crear `modules/Academic/Tests/Feature/EnrollmentTest.php`.

Cobertura minima:

- `401` sin autenticacion
- `403` para rol sin permiso suficiente
- create individual exitoso
- create bulk exitoso con resultados por usuario
- create institutional exitoso
- list con filtros por `course_id`, `user_id`, `organization_id`, `status`, `source`
- show exitoso
- activate, complete y cancel exitosos
- `422` por ids invalidos, status invalido, fechas inconsistentes y organization faltante en institucional
- `404` para curso o enrollment inexistente
- `409` para duplicado activo/pending
- `409` para transiciones invalidas de lifecycle

### Authorization unit tests

Extender `modules/Authorization/Tests/Unit/Domain/Services/RolePermissionsTest.php`.

Cobertura minima:

- `SuperAdmin` recibe `ViewEnrollments` y `ManageEnrollments`
- `InstitutionalAdmin` recibe `ViewEnrollments` y `ManageEnrollments`
- `Teacher` recibe `ViewEnrollments` pero no `ManageEnrollments`
- `Student` no recibe ninguno

## Out of Scope

Esta entrega no debe:

- reescribir progreso, avance o reglas de acceso por enrollment para examenes
- introducir scoping organizacional adicional fuera del permiso ya definido
- cambiar contratos CQRS ya aprobados
- agregar web UI

## Implementation Notes

- mantener naming y estructura alineados con `CourseController` y rutas actuales de Academic
- usar `whereUuid()` en rutas con ids
- devolver siempre el envelope `{ "data": ... }` en respuestas exitosas
- dejar `source` controlado por el endpoint y no por el cliente en los creates
