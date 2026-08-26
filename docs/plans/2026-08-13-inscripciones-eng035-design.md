# ENG-035 — Inscripciones (Design)

## Objetivo

Introducir el modelo formal de matrícula (`Enrollment`) para vincular usuarios con cursos dentro de `Modules/Academic`, cubriendo inscripción individual, inscripción masiva, asignación institucional directa, fechas de inicio/cierre y estados de matrícula.

La historia debe dejar la base transaccional y de permisos necesaria para que historias posteriores de progreso (`ENG-036`) y reglas de avance (`ENG-037`) dependan de una matrícula real y no solo del usuario autenticado.

## Alcance

Incluye:

- inscripción individual a curso
- inscripción masiva a curso
- asignación institucional directa `organization -> user -> course`
- fechas `starts_at` y `ends_at`
- estados de matrícula
- listado y detalle de matrículas
- transiciones de estado (`activate`, `complete`, `cancel`)
- validaciones para evitar duplicados activos o pendientes

No incluye:

- cohorts, grupos o secciones
- sincronización externa de matrículas
- migración inmediata de todos los flujos existentes para exigir matrícula
- progreso de curso, reglas de desbloqueo o LRS

## Decisiones de diseño

### 1. Agregado nuevo en Academic

Se crea un agregado `Enrollment` dentro de `Modules/Academic` en lugar de modelar la matrícula en `Authorization` u `Organization`.

Razón:

- la matrícula pertenece al dominio académico
- su identidad principal es la relación entre un usuario y un curso
- será la base natural para progreso, reglas de avance, trazabilidad de aprendizaje y elegibilidad para evaluaciones

### 2. Asignación institucional directa sin cohorts

La asignación institucional de esta primera versión será directa: una organización asigna usuarios concretos a cursos concretos.

Se evita introducir cohorts o grupos porque:

- no existen todavía como entidad transversal en el repo
- ampliar el modelo ahora retrasaría la entrega de la matrícula base
- la futura capa de cohorts puede construirse encima de `Enrollment`

### 3. Matrícula como historial, no como relación efímera

Las matrículas no se borran para reflejar cancelaciones o finalizaciones. En su lugar, cambian de estado y conservan trazabilidad.

Esto permite:

- auditar cuándo un usuario fue inscrito
- distinguir cancelación de finalización
- soportar reingresos o reinscripciones futuras sin perder historial

## Modelo de dominio

### Agregado `Enrollment`

Campos:

- `id`
- `course_id`
- `user_id`
- `organization_id` nullable
- `status`
- `source`
- `starts_at` nullable
- `ends_at` nullable
- `enrolled_at`

### Enum `EnrollmentStatus`

- `pending`
- `active`
- `completed`
- `canceled`

Semántica:

- `pending`: creada pero aún no activa por regla temporal u operativa
- `active`: matrícula vigente
- `completed`: finalizada normalmente
- `canceled`: anulada antes de finalizar

### Enum `EnrollmentSource`

- `individual`
- `bulk`
- `institutional`

Semántica:

- `individual`: alta puntual de un usuario en un curso
- `bulk`: alta derivada de operación masiva
- `institutional`: alta asociada a una organización concreta

## Reglas de negocio

### Duplicados

No se debe permitir más de una matrícula `pending` o `active` para la misma combinación `user_id + course_id`.

Sí puede existir historial adicional cuando la matrícula previa esté `completed` o `canceled`.

### Organización

- `organization_id` es obligatorio cuando `source = institutional`
- `organization_id` debe ser `null` cuando `source` es `individual` o `bulk`, salvo que más adelante se amplíe explícitamente el modelo

### Fechas

- `starts_at` es opcional
- `ends_at` es opcional
- si ambas existen, `ends_at >= starts_at`
- una matrícula puede crearse en `pending` si todavía no debe considerarse activa por fecha o por decisión operativa

### Estados

Reglas mínimas de transición:

- `pending -> active`
- `pending -> canceled`
- `active -> completed`
- `active -> canceled`

No se permitirá:

- completar una matrícula ya cancelada
- cancelar una matrícula ya completada
- activar una matrícula ya completada o cancelada

## Arquitectura de aplicación

Se mantiene el patrón CQRS existente en `Academic`.

### Commands

- `CreateEnrollmentCommand`
- `CreateBulkEnrollmentsCommand`
- `CreateInstitutionalEnrollmentCommand`
- `ActivateEnrollmentCommand`
- `CompleteEnrollmentCommand`
- `CancelEnrollmentCommand`

### Queries

- `GetEnrollmentQuery`
- `ListEnrollmentsQuery`

### Responses

- `EnrollmentResponse`
- `EnrollmentListItemResponse`
- `BulkEnrollmentResponse`

### Handlers

- `CreateEnrollmentHandler`
- `CreateBulkEnrollmentsHandler`
- `CreateInstitutionalEnrollmentHandler`
- `ActivateEnrollmentHandler`
- `CompleteEnrollmentHandler`
- `CancelEnrollmentHandler`
- `GetEnrollmentHandler`
- `ListEnrollmentsHandler`

## Persistencia

Nueva tabla `academic_enrollments`.

Columnas previstas:

- `id` uuid pk
- `course_id` uuid fk a `academic_courses.id`
- `user_id` uuid fk a `users.id`
- `organization_id` uuid nullable
- `status` string(30)
- `source` string(30)
- `starts_at` timestamp nullable
- `ends_at` timestamp nullable
- `enrolled_at` timestamp
- `created_at`
- `updated_at`

Índices previstos:

- `course_id`
- `user_id`
- `organization_id`
- compuesto para listados frecuentes (`course_id`, `status`) y (`user_id`, `status`)

La protección contra duplicados activos/pending puede resolverse de dos formas:

1. restricción parcial a nivel SQL si el motor/convenciones del proyecto lo facilitan
2. validación transaccional en repositorio + índice auxiliar conservador

Recomendación: implementar validación transaccional primero para mantener portabilidad y claridad en tests.

## API HTTP

### Crear inscripción individual

`POST /api/v1/academic/enrollments`

Payload base:

```json
{
  "course_id": "uuid",
  "user_id": "uuid",
  "starts_at": "2026-09-01T00:00:00Z",
  "ends_at": "2026-12-01T00:00:00Z",
  "status": "pending"
}
```

### Crear inscripción masiva

`POST /api/v1/academic/enrollments/bulk`

Payload base:

```json
{
  "course_id": "uuid",
  "user_ids": ["uuid-1", "uuid-2"],
  "starts_at": null,
  "ends_at": null,
  "status": "active"
}
```

Respuesta esperada: resumen total + resultados por usuario.

### Crear asignación institucional

`POST /api/v1/academic/enrollments/institutional`

Payload base:

```json
{
  "course_id": "uuid",
  "user_id": "uuid",
  "organization_id": "uuid",
  "starts_at": null,
  "ends_at": null,
  "status": "active"
}
```

### Consultas

- `GET /api/v1/academic/enrollments`
- `GET /api/v1/academic/enrollments/{enrollmentId}`

Filtros soportados:

- `course_id`
- `user_id`
- `organization_id`
- `status`
- `source`

### Transiciones

- `POST /api/v1/academic/enrollments/{enrollmentId}/activate`
- `POST /api/v1/academic/enrollments/{enrollmentId}/complete`
- `POST /api/v1/academic/enrollments/{enrollmentId}/cancel`

## Permisos

Se agregarán dos permisos nuevos:

- `enrollments.view`
- `enrollments.manage`

Regla recomendada inicial:

- `SuperAdmin`: view + manage
- `InstitutionalAdmin`: view + manage
- `Teacher`: view
- `Student`: ninguno

La operación institucional debe además validar coherencia organizacional cuando corresponda.

## Integración con historias existentes

`ENG-035` no debe reescribir de inmediato `ExamAttempt`, `Exam`, `Progress` ni reglas de avance.

La meta es dejar lista la entidad oficial de matrícula para que:

- `ENG-036` modele progreso por matrícula
- `ENG-037` aplique reglas de avance sobre matrícula y curso
- futuras validaciones de acceso a evaluaciones puedan apoyarse en inscripción activa

## Testing

### Unit

- `EnrollmentTest`
- tests de enums/value objects nuevos
- tests de transiciones de estado
- tests de invariantes de fechas, organización y duplicados lógicos

### Integration

- `EloquentEnrollmentRepositoryTest`
- persistencia completa
- filtros por curso, usuario, organización, estado y source
- actualización de estado

### Feature

- creación individual
- creación masiva
- creación institucional
- list/show
- activate/complete/cancel
- permisos y autenticación
- conflictos por duplicado

### Provider

- registro CQRS de commands/queries/handlers nuevos

## Riesgos controlados

- la inscripción masiva puede crecer de alcance; en esta historia se limita a un solo curso por operación
- no se introduce cohorts para evitar sobrediseño
- no se migra todavía la autorización de intentos a matrícula activa para mantener la historia acotada

## Resultado esperado

Al cerrar `ENG-035`, el sistema debe poder registrar y consultar matrículas de usuarios a cursos de forma individual, masiva e institucional, con estados y fechas explícitas, dejando una base estable para progreso y reglas de avance.
