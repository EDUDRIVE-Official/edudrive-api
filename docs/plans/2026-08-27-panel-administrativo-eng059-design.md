# ENG-059 — Panel administrativo API: alcance acordado

Primera historia de la Fase 12 — Administración y operación. A diferencia de las historias anteriores, esta cubre siete áreas del roadmap (Usuarios, Organizaciones, Cursos, Evaluaciones, Reportes, Configuración, Operación del sistema) con niveles de madurez muy distintos. Antes de acordar el alcance con el usuario, se investigó el estado real del backend (vía agentes de exploración) para no proponer reconstruir lo que ya existe.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Cursos y Evaluaciones** (`Modules\Academic`): CRUD completo y maduro ya existe (`courses.manage`/`courses.view`, `exams.manage`/`exams.view`, `questions.manage`/`questions.view`, ciclo de vida de publicación, etc.).
- **Usuarios** (`Modules\Identity`): sin API administrativa — solo registro, login, activación de autoservicio sin permiso, y consulta del propio perfil (`/auth/me`).
- **Organizaciones** (`Modules\Organization`): solo listar (sin permiso) y crear/agregar sede (`organizations.manage`) — sin ver detalle ni actualizar.
- **Reportes, Configuración, Operación del sistema**: no existe ninguna capacidad — completamente nuevos (*greenfield*).
- **Auditoría** (`Modules\Audit`): existe como servicio interno de escritura (`AuditLogger`/`AuditRepository::save()`), usado por otros módulos para registrar eventos, pero **no tiene capa HTTP ni método de consulta** — los datos existen pero no hay forma de leerlos.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Usuarios y Organizaciones**: alcance mínimo viable.
   - Usuarios: listar, ver detalle, activar/desactivar una cuenta — reutilizando el método `User::activate()`/`User::deactivate()` ya existentes en el dominio (no se introduce un concepto nuevo de "suspender"; "suspender" se mapea a `deactivate()`, que ya transiciona a `Inactive`). Sin reseteo de contraseña administrativo, sin acciones masivas, sin impersonación.
   - Organizaciones: agregar ver detalle y actualizar (renombrar) — ya existen listar y crear.
2. **Cursos y Evaluaciones**: se reutilizan tal cual las APIs existentes de `Modules\Academic`. Sin cambios en esta historia.
3. **Reportes**: un único endpoint de resumen agregado (`GET /admin/reports/summary`) con conteos simples calculados en cada consulta — usuarios totales, inscripciones totales, logros otorgados, certificados emitidos, sesiones de simulación. Sin filtros por fecha/organización, sin exportación, sin motor de reportes configurable.
4. **Configuración y Operación del sistema**: alcance mínimo.
   - Configuración: almacén clave-valor simple (`SystemSetting`) para ajustes del sistema.
   - Operación: un endpoint de salud agregada (conectividad a base de datos) y una API de solo lectura sobre los registros de auditoría ya existentes en `Modules\Audit` — sin nueva lógica de dominio de auditoría, solo la capacidad de consulta que falta.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Nuevo módulo `Modules\Admin`** (bounded context de la Fase 12), que alberga Reportes, Configuración y Operación del sistema — conceptos genuinamente nuevos y transversales. Usuarios y Organizaciones, en cambio, se extienden directamente en sus módulos propietarios (`Modules\Identity`, `Modules\Organization`), mismo criterio que "cada permiso vive donde vive su agregado" usado en toda la sesión.
- **`Modules\Identity` sigue su propio patrón preexistente** (UseCase con inyección directa en el controlador, sin `CommandBus`/`QueryBus`/`MessageHandlerRegistry`) — es el único módulo que no usa el bus CQRS; se respeta su convención local en vez de migrarlo.
- **Reportes lee directamente los modelos Eloquent de otros módulos** desde `Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemSummaryRepository`, detrás de una interfaz de dominio propia (`SystemSummaryRepository`) — es una lectura agregada de solo conteo, sin invariantes de dominio, por lo que no se filtra a través de los repositorios de dominio de cada módulo (que no exponen un método `count()`). Se documenta como una excepción deliberada al aislamiento estricto entre módulos, limitada a lectura de reportes.
- **`Modules\Audit` se extiende con un único método nuevo**, `AuditRepository::all(): list<AuditEntry>` (antes solo tenía `save()`) — la lectura para el panel administrativo la expone `Modules\Admin` (que depende de la interfaz de `Modules\Audit`), ya que "Operación del sistema" es una preocupación administrativa, no de auditoría en sí.
- **Salud agregada minimalista**: solo verifica conectividad a base de datos (`SELECT 1`). Los demás módulos solo exponen un ping fijo `{status: 'available'}` sin señal real de salud que agregar — inventar un estado de salud por módulo sería decorativo. Se documenta como diferido: salud real por módulo, cuando cada uno tenga algo real que reportar.
- **`SystemSetting` es un registro simple `key`/`value`**, sin categorías, sin tipado de valores (todo texto), sin historial de cambios.
- **Permisos nuevos**: `users.manage`/`users.view`, `reports.view`, `system_settings.manage`/`system_settings.view`, `system_operations.view`. Los dos últimos pares (`system_settings.*`, `system_operations.view`) se otorgan **únicamente a SuperAdmin** — mismo criterio que `roles.manage` (configuración y operación global del sistema, no una preocupación por institución) — a diferencia de `users.manage`/`reports.view`, que sí se otorgan también a InstitutionalAdmin, mismo patrón que el resto de los permisos `.manage`/`.view` de la sesión.
- **Organizaciones — alcance de "actualizar"**: solo renombrar (`Organization::rename()`). Cambiar el tipo institucional o gestionar sedes desde este endpoint queda fuera de alcance (agregar una sede ya tiene su propio endpoint desde antes).

## Incluye (del roadmap)

- Usuarios.
- Organizaciones.
- Cursos.
- Evaluaciones.
- Reportes.
- Configuración.
- Operación del sistema.

## Diferido explícitamente

- Reseteo de contraseña administrativo, acciones masivas sobre usuarios, impersonación de cuenta.
- Cambiar el tipo institucional de una organización o gestionar sedes desde el endpoint de actualización.
- Motor de reportes configurable, filtros por fecha/organización, exportación.
- Categorías/tipado de valores/historial de cambios en la configuración del sistema.
- Salud real por módulo (más allá de la conectividad a base de datos).
- Filtros de auditoría por usuario/entidad/rango de fechas (la primera versión de la API de lectura es una lista simple).
