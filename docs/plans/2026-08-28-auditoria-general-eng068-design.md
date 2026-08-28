# ENG-068 — Auditoría general: alcance acordado

Segunda historia de la Fase 14 — Seguridad y cumplimiento. El roadmap pide ocho campos por entrada de auditoría (Actor, Acción, Recurso, Fecha, IP, Correlation ID, Resultado, Datos modificados) sin especificar qué acciones deben auditarse.

## Estado previo encontrado (investigación, no una decisión del usuario)

- `Modules\Audit` ya existe: `AuditEntry` (action, userId, entity, entityId, metadata, id, occurredAt) y `AuditLogger::log()`, implementado por `DatabaseAuditLogger` → `EloquentAuditRepository`.
- **Actor/Acción/Recurso/Fecha ya están bien conectados**. **IP tiene columna en la base de datos (`ip`, `user_agent`) pero nunca se escribe** — `EloquentAuditRepository::save()` nunca las incluye, quedan siempre `null`. **Correlation ID no existe en absoluto** (ninguna columna, ningún campo en el DTO) — pero `Modules\Foundation\Presentation\Http\Middleware\CorrelationId` ya genera uno por petición y lo guarda en `Illuminate\Support\Facades\Context` (`Context::add('correlation_id', ...)`), legible desde cualquier lugar sin necesitar el objeto `Request`. **"Resultado" no existe**: solo se auditan logins exitosos, nunca los fallidos (un hueco real de seguridad, no solo un campo faltante).
- Solo 3 casos de uso llaman hoy a `AuditLogger::log()`: `LoginUserUseCase`, `LogoutUserUseCase`, `LogoutAllUsersUseCase` (los tres en `Modules\Identity`). `LogoutUserUseCase` tiene además un hueco propio: nunca establece `userId` en la entrada (solo guarda `token_id` en metadata). Ninguno de los tres recibe la petición HTTP, IP, ni el id del actor como parámetro — operan solo con primitivos.
- Hay casi 90 comandos de escritura en todo el backend; auditar todos excede una sola historia.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Cobertura**: autenticación (extendida a login fallido) + asignación de roles (`Modules\Authorization`) + cambios de configuración del sistema (`Modules\Admin`) — las acciones de mayor valor de seguridad, no los ~90 comandos de escritura de todo el backend.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **IP y Correlation ID se enriquecen en la capa de infraestructura (`DatabaseAuditLogger`), no en cada llamador**: `AuditEntry` gana campos opcionales `ip`/`correlationId` (por defecto `null`), pero ningún caso de uso necesita pasarlos explícitamente — `DatabaseAuditLogger` inyecta `Illuminate\Http\Request` (se resuelve una vez por petición, el binding no es singleton) y completa `ip` desde `$request->ip()` y `correlationId` desde `Context::get('correlation_id')` si el llamador no los proporcionó, antes de persistir. Evita tener que enhebrar IP/Correlation ID manualmente por cada caso de uso nuevo o existente.
- **"Resultado" (`outcome`)**: nuevo campo `string $outcome = 'success'` en `AuditEntry` (con ese valor por defecto para no romper los call sites existentes que no lo establecen). Login fallido pasa explícitamente `outcome: 'failure'`.
- **Login fallido ahora se audita**: `LoginUserUseCase` captura `InvalidCredentials`/`UserCannotAuthenticate`, registra una entrada con `outcome: 'failure'` y **relanza la excepción** — el comportamiento HTTP no cambia, solo se agrega el registro.
- **`LogoutUserUseCase` gana un parámetro `userId`** (ya disponible en `LogoutController` vía `$request->user()`), corrigiendo el hueco donde el actor nunca quedaba registrado.
- **"Datos modificados" se resuelve concretamente en configuración del sistema**: `SetSystemSettingHandler` ya lee el valor anterior antes de sobrescribirlo (`findByKey()`) — la entrada de auditoría registra `old_value`/`new_value` en `metadata`, el caso más natural para ese campo del roadmap dentro del alcance elegido.
- **`AssignRoleCommand`/`SetSystemSettingCommand` ganan un parámetro `actorId`** (el usuario autenticado que ejecuta la acción, ya disponible en ambos controladores vía `$request->user()`) — el "Actor" de estas dos acciones es quien las ejecuta, no el usuario objetivo de la asignación de rol.
- **Sin filtros nuevos en `GetAuditLogsQuery`**: se agregan los campos nuevos a `AuditLogResponse` (ip, correlation_id, outcome) pero no se construye ningún filtro por esos campos en esta historia — mismo alcance mínimo ya usado en ENG-059 para esa consulta.

## Incluye (del roadmap)

- Actor, Acción, Recurso, Fecha (ya cubiertos, sin cambios).
- IP (ahora se persiste de verdad).
- Correlation ID (nuevo).
- Resultado (nuevo, con login fallido como primer caso auditado).
- Datos modificados (nuevo, en cambios de configuración del sistema).

## Diferido explícitamente

- Auditoría de los ~90 comandos de escritura restantes del backend.
- Filtros de consulta sobre los registros de auditoría (por actor, acción, rango de fechas, resultado).
- Persistencia de "datos modificados" para acciones distintas a la configuración del sistema.
