# ENG-014 — Contexto organizacional: diseño

**Fase:** 2 — Autorización y gobierno de acceso.
**Alcance acordado:** primitivas de autorización con alcance por
organización + aplicarlas al único consumidor real ya demostrado
(recomendado, elegido por el usuario). El retrofit de otros endpoints
que hoy aceptan `organization_id` queda documentado como candidato
futuro, no se toca en esta historia.

## Contexto y hallazgos de la investigación

Un agente en background confirmó que ENG-014 es transversal pero que
solo existe **un** consumidor real y demostrado del gap:

- `RoleAssignmentPermissionChecker::userHasPermission()` es un booleano
  global: itera todas las `RoleAssignment` del usuario y concede si
  CUALQUIERA de sus roles otorga el permiso, ignorando por completo
  `organizationId`. Lo consume `EnsurePermission`, el middleware
  `permission:*` usado en casi toda la aplicación.
- El modelo de datos ya soporta múltiples `RoleAssignment` por usuario,
  cada una con su propio `organizationId` opcional (`null` = sin
  organización) — confirmado con un test que asigna Docente en una
  organización y Estudiante en otra al mismo usuario.
- **Gap real y demostrado**: `OrganizationReportController` (los 4
  reportes institucionales de `Modules\Academic`) recibe
  `organization_ids` como filtro de query string opcional; si viene
  vacío, `ReportOrganizationResolver::resolve([])` devuelve **todas**
  las organizaciones del sistema (`$this->organizations->all()`). Un
  administrador institucional con el permiso `reports.view` ve hoy los
  reportes de todas las instituciones, no solo la suya.
- No existe ningún mecanismo de "cambio de contexto" (header, sesión).
  Otros endpoints que aceptan `organization_id` (listado de
  enrollments, asignación de roles) son parámetros de consulta/escritura
  sin relación con la identidad del solicitante — no se tocan aquí.
- Roles: `SuperAdmin` (candidato natural a global) e
  `InstitutionalAdmin` (candidato natural a institucional) son los dos
  únicos roles que otorgan `reports.view`; `Teacher`/`Student` no.

## Decisión de diseño

### No se toca `PermissionChecker::userHasPermission()`

Su firma y comportamiento actual (booleano global, sin organización) se
preservan exactamente — es el núcleo consumido por `EnsurePermission` en
casi todas las rutas de la aplicación. Cambiar su semántica habría
significado auditar y actualizar cada invocación del middleware
`permission:*` en todos los módulos, exactamente el retrofit masivo que
el usuario decidió no hacer en esta historia. En su lugar, se agrega una
capacidad **aditiva y separada**.

### `Modules\Authorization\Application\Services\AccessibleOrganizationsResolver`

Nueva interfaz: `resolveForPermission(string $userId, Permission $permission): ?array`.
Devuelve `null` si el usuario tiene, para ese permiso, al menos una
`RoleAssignment` con `organizationId === null` (grant global — ej. un
`SuperAdmin` asignado sin organización). Si no, devuelve la lista de
`organizationId` de sus asignaciones cuyo rol otorga ese permiso — su
"alcance institucional" para esa acción concreta. Implementación
(`Infrastructure/Services/RoleAssignmentAccessibleOrganizationsResolver`)
reutiliza `RoleAssignmentRepository`/`RolePermissions`, mismo estilo que
`RoleAssignmentPermissionChecker`.

### Nueva excepción `Modules\Authorization\Application\Exceptions\OrganizationNotAccessible`

`DomainException`, 403, `ORGANIZATION_NOT_ACCESSIBLE` — se lanza cuando
un solicitante con alcance restringido pide explícitamente una
organización fuera de su alcance (en vez de devolver silenciosamente un
subconjunto, lo cual podría enmascarar un error de autorización).

### `OrganizationReportController` con alcance aplicado

Deja de ser una clase con métodos estáticos: gana
`AccessibleOrganizationsResolver` inyectado por constructor. Antes de
despachar cada query:

1. Resuelve el alcance del usuario autenticado para `Permission::ViewReports`.
2. Si es `null` (sin restricción): comportamiento idéntico al actual.
3. Si es una lista concreta y la petición no pidió `organization_ids`
   explícitos: se usa esa lista como filtro (en vez de "todas").
4. Si la petición sí pidió `organization_ids` explícitos: se valida que
   estén contenidos en el alcance; si alguno no lo está, lanza
   `OrganizationNotAccessible` (403).

Los 4 handlers (`GetOrganization{Participation,Completion,Performance,Adoption}ReportHandler`)
y `ReportOrganizationResolver` no cambian — ya reciben `organizationIds`
resuelto, la lógica de alcance vive enteramente en el controlador.

## Fuera de alcance (documentado explícitamente)

- Cualquier retrofit de otros endpoints que ya aceptan `organization_id`
  (listado de enrollments, asignación de roles) — quedan como candidatos
  a aplicar el mismo patrón incrementalmente en historias futuras.
- Cualquier mecanismo explícito de "cambio de contexto" (header, sesión,
  endpoint para "actuar como organización X") — el alcance ya se deriva
  implícitamente de las `RoleAssignment` del usuario en cada petición,
  sin necesidad de un estado de sesión adicional.
- Cambios a `PermissionChecker`, `EnsurePermission`, o cualquier otra
  ruta protegida por `permission:*` fuera de los 4 reportes
  institucionales.

## Plan de verificación

TDD: `AccessibleOrganizationsResolver` (Unit, con fake de
`RoleAssignmentRepository`), luego `OrganizationReportController`
(Feature) — un `InstitutionalAdmin` asignado a una organización
específica solo ve esa organización sin filtro explícito, recibe 403 si
pide una organización ajena, y un `SuperAdmin` (asignación global) sigue
viendo todas. Pint y PHPStan (`--memory-limit=512M`) sobre los archivos
tocados y luego sobre el repo completo. Suite completa de
`Modules\Authorization` y `Modules\Academic` vía `./vendor/bin/pest`.
