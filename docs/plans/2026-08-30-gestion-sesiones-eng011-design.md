# ENG-011 — Gestión de sesiones y dispositivos: diseño

**Fase:** 1 — Identidad y acceso.
**Alcance acordado:** revocación individual de sesiones (recomendado);
"políticas de seguridad por aplicación" queda fuera de alcance
(recomendado, sin decisión de producto que la sustente).

## Contexto y hallazgos de la investigación

Un agente en background confirmó que la mayoría de las viñetas ya
estaban cubiertas antes de esta historia:

- **Nombre del dispositivo, fecha de creación, último uso**: ya los
  devuelve `GET /api/v1/auth/sessions`
  (`SessionsController`/`GetUserSessionsUseCase`/`SanctumSessionRepository`),
  usando las columnas nativas de Sanctum (`name`, `created_at`,
  `last_used_at`). `token_name` ya es un campo libre que el cliente
  puede usar como nombre de dispositivo si lo desea (validado, máx 100
  caracteres) — no hace falta ningún cambio.
- **Expiración de tokens**: ya es real, no simulada — `config/sanctum.php`
  (`SANCTUM_EXPIRATION_MINUTES`, 30 días por defecto) y confirmado por
  `SanctumTokenExpirationTest.php` con una expiración real de Sanctum
  contra la base de datos, no un mock.
- **Revocación individual**: **no existe**. Solo hay `logout` (revoca el
  token actual) y `logout-all` (revoca todos) — es todo o nada, sin
  forma de cerrar una sesión específica de otro dispositivo sin cerrar
  las demás.
- **Políticas de seguridad por aplicación**: no hay ninguna definición
  en el proyecto de qué "aplicaciones" existen ni qué políticas deberían
  diferir. Todos los tokens emitidos hoy tienen las mismas `abilities`
  (`['*']`) y la misma expiración global, sin importar si el login vino
  de la web (`LoginWebController`, `tokenName` hardcodeado `'web'`) o de
  la API. El usuario decidió no construir diferenciación especulativa
  sin una decisión de producto real.

## Decisión de diseño

### `AccessTokenRevoker::revokeForUser(string $userId, string $tokenId): bool`

Nuevo método en la interfaz (`Application/Services`) e implementación en
`SanctumAccessTokenRevoker`: borra el token solo si pertenece al usuario
dado (`tokenable_id = $userId`), devolviendo si realmente borró algo —
evita que un usuario revoque el token de otro adivinando su id.

### `RevokeSessionUseCase`

Nuevo caso de uso: llama a `revokeForUser(userId, tokenId)`; si devuelve
`false` (no existía o no pertenece al usuario), lanza
`Modules\Identity\Application\Exceptions\SessionNotFound`
(`DomainException`, 404, `SESSION_NOT_FOUND` — mismo código de estado
sin distinguir "no existe" de "no te pertenece", evita revelar
información). Si tiene éxito, audita (`auth.session_revoked`, metadata
`token_id`), mismo patrón que `LogoutUserUseCase`/`LogoutAllUsersUseCase`.

### `DELETE /api/v1/auth/sessions/{tokenId}`

Nueva ruta bajo el grupo `auth:sanctum` ya existente (junto a
`/sessions`, `/logout`, `/logout-all`), sin rate limiter adicional (ya
protegida por autenticación, igual que el resto del grupo).
`RevokeSessionController` resuelve `tokenId` de la ruta y `userId` del
usuario autenticado. `SessionNotFound` se resuelve automáticamente vía
el renderer global de `DomainException` (404).

## Fuera de alcance (documentado explícitamente)

- Cualquier diferenciación de `abilities`/expiración por tipo de
  cliente/aplicación ("políticas de seguridad por aplicación") — no hay
  ninguna decisión de producto que especifique qué aplicaciones existen
  ni qué políticas necesitan.
- Cambios a `token_name`/nombre de dispositivo, fecha de creación o
  último uso — ya funcionan correctamente.
- Cualquier cambio a la expiración global de Sanctum — ya es real y
  configurable.

## Plan de verificación

TDD: `AccessTokenRevoker::revokeForUser()` (fake en tests de
`RevokeSessionUseCase`), luego el endpoint completo en Feature (revoca
una sesión específica sin afectar las demás, rechaza revocar la sesión
de otro usuario con 404, rechaza un `tokenId` inexistente con 404). Pint
y PHPStan (`--memory-limit=512M`) sobre los archivos tocados y luego
sobre el repo completo. Suite de `Modules\Identity` completa vía
`./vendor/bin/pest`.
