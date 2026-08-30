# ENG-015 — Auditoría de accesos: diseño

**Fase:** 2 — Autorización y gobierno de acceso.
**Alcance acordado:** solo el gap real encontrado (activación/desactivación
administrativa de cuentas no auditada); "Cambio de contraseña"/"Cambio
de correo" quedan fuera de alcance porque no existe ningún endpoint que
dispare esas acciones (recomendado, elegido por el usuario).

## Contexto y hallazgos de la investigación

Un agente en background confirmó que la mayoría de las viñetas ya
estaban cubiertas por trabajo de esta misma sesión (ENG-008.8/009/010/011):

- **Inicio de sesión, Intentos fallidos**: `LoginUserUseCase` audita
  `auth.login` en éxito y fallo.
- **Cierre de sesión**: `auth.logout`/`auth.logout_all`.
- **Revocación de tokens**: `auth.logout`, `auth.logout_all`,
  `auth.session_revoked` (ENG-011).
- **Cambios de roles y permisos**: `AssignRoleHandler` audita
  `authorization.role_assigned`. No existe ningún caso de uso para
  **revocar** un rol — es un gap real, pero ya está explícitamente
  diferido a ENG-018 ("Membresías organizacionales", nota existente:
  "no existe... revocación, el modelo es de solo inserción, sin
  endpoint DELETE"). No se reconstruye aquí.
- **Cambio de contraseña, Cambio de correo** (autenticado, distinto del
  flujo de recuperación por token de ENG-009): **no existe ningún
  endpoint ni caso de uso**. `User::changeEmail()` existe en el dominio
  pero ningún controlador lo invoca. El usuario decidió no construir
  estas dos features de perfil nuevas dentro de una historia de
  auditoría — quedan documentadas como bloqueadas hasta que exista el
  endpoint correspondiente.
- **Gap real encontrado, dentro de alcance**: `ActivateUserUseCase` y
  `DeactivateUserUseCase` (activación/desactivación administrativa de
  cuentas, `/api/v1/users/{userId}/activate|deactivate`, protegidas por
  `permission:users.manage`) no auditan nada — ninguna de las dos
  inyecta `AuditLogger`. Es una acción sensible sobre el acceso de un
  usuario que hoy no deja rastro en `Modules\Audit`.

También se encontraron gaps reales en el propio módulo `Modules\Audit`
(sin filtros/paginación en `GetAuditLogsQuery`, sin retención) — quedan
fuera de alcance: son limitaciones de la API de consulta, no del
catálogo de eventos que esta historia pide cubrir.

## Decisión de diseño

`ActivateUserCommand`/`DeactivateUserCommand` ganan `actorId: string`
(quien ejecuta la acción, mismo patrón que `AssignRoleCommand`).
`ActivateUserUseCase`/`DeactivateUserUseCase` ganan `AuditLogger` y
auditan tras guardar: `identity.account_activated`/
`identity.account_deactivated`, `userId: $command->actorId` (el actor,
no el usuario objetivo), `entity: 'User'`, `entityId: $user->id()`.
`ActivateUserController`/`DeactivateUserController` ganan `Request` y
pasan `(string) $request->user()->getAuthIdentifier()` como `actorId`.

## Fuera de alcance (documentado explícitamente)

- Endpoints de cambio de contraseña/correo autenticados — no existen
  hoy, construirlos es una historia de perfil de usuario, no de
  auditoría.
- Revocación de asignaciones de rol — diferido a ENG-018.
- Filtros, paginación y retención en `Modules\Audit` — limitación de la
  API de consulta, no del catálogo de eventos auditados.

## Plan de verificación

TDD: tests unitarios de `ActivateUserUseCase`/`DeactivateUserUseCase`
con un `AuditLogger` espía, luego Feature (`AdminUserManagementTest`)
confirmando la entrada de auditoría tras activar/desactivar vía HTTP.
Pint y PHPStan (`--memory-limit=512M`) sobre los archivos tocados y
sobre el repo completo. Suite de `Modules\Identity` completa.
