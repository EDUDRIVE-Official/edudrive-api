# ENG-008.8 — Pruebas de autenticación: diseño

**Fase:** 1 — Identidad y acceso.
**Alcance acordado:** reducido (recomendado, elegido por el usuario) —
tests Feature para los 8 casos pedidos + corrección de un bug real
encontrado en el manejo de excepciones de login.

## Contexto y hallazgos de la investigación

La nota del roadmap decía que solo existía un test de integración del
repositorio de usuarios. Un agente en background confirmó que la
situación es más matizada:

- Los endpoints (`POST /api/v1/auth/login`, `GET /api/v1/auth/me`,
  `POST /api/v1/auth/logout`, `POST /api/v1/auth/logout-all`) ya existen
  y funcionan, implementados por `LoginController`/`LoginUserUseCase`,
  `MeController`/`GetAuthenticatedUserUseCase`,
  `LogoutController`/`LogoutUserUseCase`,
  `LogoutAllController`/`LogoutAllUsersUseCase`.
- Ya existen tests que tocan estos endpoints **incidentalmente**, con
  otro propósito: `AuthAuditLogTest` (auditoría), `RateLimitingTest`
  (throttling), `SanctumTokenExpirationTest` (expiración, contra otra
  ruta). Ninguno cubre explícitamente los 8 casos pedidos como
  aserciones de contrato HTTP.
- **Usuario inactivo**: la lógica de rechazo ya existe —
  `UserStatus::canAuthenticate()` solo es `true` para `Active`;
  `LoginUserUseCase` lanza `UserCannotAuthenticate` (403,
  `USER_CANNOT_AUTHENTICATE`) si falla. Solo falta el test.
- **Revocación de tokens**: no existe un endpoint para revocar un token
  específico por id; el concepto ya está cubierto por `logout`
  (`AccessTokenRevoker::revokeCurrent()`, revoca solo el token actual) y
  `logout-all` (`revokeAllForUser()`, revoca todos). El test verifica que
  un token revocado deja de servir para autenticar.
- **Bug real encontrado**: `InvalidCredentials` (lanzada tanto para
  contraseña incorrecta como para email inexistente — no distinguir es
  la práctica de seguridad correcta) extiende `RuntimeException` plano,
  no el `DomainException` base (a diferencia de sus hermanas
  `UserCannotAuthenticate`/`UserNotFound`), así que Laravel no la mapea a
  un `ApiErrorResponse` propio y hoy produce **HTTP 500** en vez de un
  401 semántico — confirmado por `AuthAuditLogTest::assertStatus(500)`,
  que además queda desactualizado por este fix.

## Decisión de diseño

### A. Corregir `InvalidCredentials`

Pasa a extender `Modules\Foundation\Domain\Exceptions\DomainException`
con `errorCode: 'INVALID_CREDENTIALS'`, `statusCode: 401` — mismo patrón
que `UserCannotAuthenticate`/`UserNotFound`. Verificado que
`LoginWebController` la captura por nombre de clase exacto
(`catch (InvalidCredentials|UserCannotAuthenticate)`), así que el cambio
de clase padre no rompe el flujo web. Se actualiza
`AuthAuditLogTest::assertStatus(500)` → `assertStatus(401)`.

### B. Nuevo `modules/Identity/Tests/Feature/LoginTest.php`

Cubre los 8 casos pedidos:

1. Login correcto → 200, `data.token.access_token` presente.
2. Credenciales inválidas (contraseña incorrecta) → 401,
   `INVALID_CREDENTIALS`.
3. Usuario inexistente → 401, `INVALID_CREDENTIALS` (mismo código que #2,
   no se revela si el email existe).
4. Usuario inactivo (`deactivate()`) → 403, `USER_CANNOT_AUTHENTICATE`.
5. Acceso sin token a `/me` → 401 (middleware `auth:sanctum`).
6. Acceso con token a `/me` → 200, datos del usuario autenticado.
7. Logout → revoca el token actual; una petición posterior con ese mismo
   token a `/me` responde 401.
8. Revocación de tokens (`logout-all`) → con dos tokens emitidos para el
   mismo usuario, tras `logout-all` ambos dejan de servir para `/me`.

## Fuera de alcance (documentado explícitamente)

- Endpoint de revocación de un token específico por id (no lo pide la
  historia; el concepto ya está cubierto por logout/logout-all).
- Cualquier cambio a `UserCannotAuthenticate`/`UserNotFound` (ya siguen
  el patrón correcto).
- Recuperación de contraseña, verificación de correo, gestión de
  sesiones y dispositivos — son ENG-009/010/011, historias separadas.

## Plan de verificación

Pint y PHPStan (`--memory-limit=512M`) sobre los archivos tocados y luego
sobre el repo completo. Suite completa de `Modules\Identity` corrida
directamente vía `./vendor/bin/pest` (no `artisan test`, por el quirk de
memoria ya conocido) para confirmar que el nuevo test pasa y que
`AuthAuditLogTest` sigue en verde tras el cambio de status code.
