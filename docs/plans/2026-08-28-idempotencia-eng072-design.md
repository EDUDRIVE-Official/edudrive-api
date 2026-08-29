# ENG-072 — Idempotencia: alcance acordado

Sexta historia de la Fase 14 — Seguridad y cumplimiento (queda ENG-072 como la última de la fase; Fase 15 — Integraciones empieza en ENG-073). El roadmap pide cinco puntos: Registro de simulaciones, Pagos, Inscripciones, Sincronizaciones móviles, Operaciones críticas.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Registro de simulaciones** y **Sincronizaciones móviles**: ya resueltos por el mismo mecanismo, construido en ENG-050. `Modules\Simulation`'s endpoints de telemetría y puntos de decisión reciben un `id` generado por el cliente (el simulador/dispositivo) por cada muestra/evento/punto, y los repositorios usan `insertOrIgnore()` sobre ese `id` como clave primaria — reenviar el mismo lote es un no-op silencioso, sin tabla de llaves de idempotencia separada. Esta decisión ya está documentada en el roadmap (nota de ENG-050): "se prefirió id por ítem, más simple y sin tabla nueva".
- **Pagos**: no existe ningún módulo de pagos en el sistema — es la historia futura ENG-077 (Fase 15+). No hay código que corregir todavía.
- **Inscripciones**: hueco real. `CreateEnrollmentHandler` y `CreateInstitutionalEnrollmentHandler` verifican duplicados con `findActiveOrPendingFor()` pero **lanzan una excepción 409** (`EnrollmentAlreadyExists`) en vez de devolver la inscripción existente — un reintento de red ante una respuesta perdida falla con un error en vez de comportarse como una operación ya completada.
- **Operaciones críticas**: dos huecos reales.
  - `IssueCertificateHandler` tiene el mismo patrón: verifica con `findByUserAndCourse()` pero lanza `CertificateAlreadyExists` (409) en vez de devolver el certificado existente.
  - `AssignRoleHandler` **no tiene ninguna protección**: no verifica si la asignación ya existe, así que reintentar crea filas duplicadas de `RoleAssignment` silenciosamente — el hueco más claro encontrado.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: se corrigen los tres huecos confirmados a nivel de aplicación (`CreateEnrollmentHandler`, `CreateInstitutionalEnrollmentHandler`, `IssueCertificateHandler` devuelven el recurso existente en vez de lanzar un conflicto; `AssignRoleHandler` gana una verificación de existencia antes de crear). "Registro de simulaciones" y "Sincronizaciones móviles" se documentan como ya satisfechos. "Pagos" se documenta sin trabajo pendiente — el patrón establecido aquí (devolver el recurso existente en vez de fallar) es la plantilla que ENG-077 deberá seguir cuando se construya.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Se difieren las restricciones de unicidad a nivel de base de datos** (índices únicos o parciales en `academic_enrollments`/`authorization_role_assignments` para cerrar la ventana de condición de carrera bajo concurrencia real) — es un problema distinto de "seguridad ante reintentos simples": la verificación de aplicación (`findActiveOrPendingFor`/`findByUserId`) ya cubre el caso común de un cliente que reintenta una petición fallida, que es el escenario que motiva esta historia. Cerrar la ventana de una doble escritura verdaderamente concurrente es un problema de control de concurrencia más amplio, no específico de esta historia.
- **Se difiere la eliminación de cuenta idempotente** (`DeleteAccountUseCase` lanza `UserNotFound` si se reintenta tras un borrado exitoso): su explotabilidad práctica es limitada porque el token de autenticación usado para la petición se elimina en cascada junto con la cuenta (ENG-070) — un reintento con el mismo token fallaría la autenticación antes de llegar siquiera al caso de uso, así que no es un hueco vivo en la práctica.
- **No se re-audita en un reintento idempotente**: `AssignRoleHandler` solo registra `authorization.role_assigned` cuando efectivamente crea una asignación nueva — devolver la existente no genera una segunda entrada de auditoría falsa.
- **Excepciones ahora sin uso se eliminaron**: `EnrollmentAlreadyExists` y `CertificateAlreadyExists` quedaron sin ningún llamador tras el cambio de comportamiento; se borraron en vez de dejarlas como código muerto.
- **`CreateBulkEnrollmentsHandler` no se modificó**: su verificación de duplicados por fila ya reporta `'created': false, 'error_code': 'ENROLLMENT_ALREADY_EXISTS'` como resultado de una fila individual dentro de un lote (mismo patrón ya usado en ENG-061 para otras importaciones masivas) — no es el mismo problema de "un cliente reintenta una sola petición", es el comportamiento esperado de un reporte de importación por lote.

## Incluye (del roadmap)

- Registro de simulaciones (confirmación, sin cambios).
- Pagos (confirmación de que no hay trabajo pendiente).
- Inscripciones (`CreateEnrollmentHandler`, `CreateInstitutionalEnrollmentHandler`).
- Sincronizaciones móviles (confirmación, sin cambios).
- Operaciones críticas (`IssueCertificateHandler`, `AssignRoleHandler`).

## Diferido explícitamente

- Restricciones de unicidad o índices parciales a nivel de base de datos para condiciones de carrera bajo concurrencia real.
- Eliminación de cuenta idempotente ante reintentos.
- Infraestructura genérica reutilizable de "llave de idempotencia" (tabla/middleware compartido) aplicable a cualquier endpoint de escritura futuro.
