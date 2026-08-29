# ENG-010 — Verificación de correo electrónico: diseño

**Fase:** 1 — Identidad y acceso.
**Alcance acordado:** flujo propio de token (mismo patrón que ENG-009),
elimina la ruta pública insegura de activación, sin middleware nuevo
para "restricción de acciones" — decisiones confirmadas por el usuario.

## Contexto y hallazgos de la investigación

Un agente en background confirmó:

- `RegisterUserUseCase` crea el usuario en `Pending` y **no envía
  ningún correo ni dispara ninguna verificación** — el usuario queda sin
  forma de activarse a sí mismo.
- **Hallazgo de seguridad real**: existe una ruta pública
  `POST /api/v1/auth/users/{userId}/activate` (sin token, sin
  autenticación, solo `throttle:activate` por IP) que activa **cualquier
  cuenta** conociendo su UUID — `ActivateUserUseCase` no valida
  propiedad del correo, solo llama a `$user->activate(...)`. Existe una
  segunda ruta al mismo controlador, protegida por `auth:sanctum` +
  `permission:users.manage` (`/api/v1/users/{userId}/activate`), que sí
  es legítima (activación administrativa). El usuario decidió **eliminar
  la ruta pública insegura**, reemplazada por el flujo de verificación
  con token de esta historia; la ruta administrativa no se toca.
- No existe ninguna tabla de tokens de verificación de correo. Se crea
  una nueva (a diferencia de ENG-009, donde se reutilizó una tabla ya
  existente).
- No existe ningún middleware/gate de correo no verificado. El único
  gate real hoy es el login (`LoginUserUseCase` exige
  `UserStatus::canAuthenticate()`, que solo es `true` en `Active`, estado
  que solo se alcanza vía `activate()`). El usuario decidió que este
  gate ya es la restricción real pedida por la viñeta "Restricción de
  acciones para correos no verificados" — no se construye middleware
  nuevo sin endpoints concretos que proteger.
- `User::changeEmail()` ya revierte a `Pending` y limpia
  `emailVerifiedAt` cuando el correo cambia — el nuevo correo
  necesitará re-verificarse con el mismo mecanismo.
- `RegisterUserUseCase` se invoca desde dos sitios: `AuthController`
  (vía contenedor) y `ImportUsersJob::importRow()` (instanciación manual
  directa, `new RegisterUserUseCase(...)`) — ambos deben actualizarse si
  se le agrega una dependencia nueva.

## Decisiones de diseño

### Domain (`modules/Identity/Domain/`)

- **`Entities/EmailVerificationToken.php`**: mismo patrón que
  `PasswordResetToken` (ENG-009) — `email`, `tokenHash` (sha256),
  `createdAt`, `TTL_MINUTES = 60`, `issue()`/`reconstitute()`,
  `matchesHash()`, `isExpired()`.
- **`Repositories/EmailVerificationTokenRepository.php`**: `save()`
  (upsert por email), `findByEmail()`, `deleteByEmail()`.
- **`Exceptions/InvalidEmailVerificationToken.php`**: `DomainException`
  (422, `INVALID_EMAIL_VERIFICATION_TOKEN`) — un único código para token
  inexistente, expirado o que no coincide (mismo principio que
  `InvalidCredentials`/`InvalidPasswordResetToken`).

### Application (`modules/Identity/Application/`)

- **`SendEmailVerificationUseCase`**: busca el usuario por email. Si no
  existe **o ya tiene el correo verificado**, no hace nada (mismo
  patrón de no revelar información que `forgot-password`). Si
  corresponde: genera token aleatorio, hash sha256, reemplaza cualquier
  token previo del mismo correo, envía el correo con el token en texto
  plano vía `EmailNotificationSender` (mismo motivo que ENG-009: no hay
  `FRONTEND_URL` decidido), audita
  (`auth.email_verification_requested`). **Reutilizada en dos puntos**:
  automáticamente al final de `RegisterUserUseCase` (nueva dependencia
  en su constructor) y por el endpoint público de reenvío — un único
  caso de uso cubre "Envío de enlace" y "Reenvío de enlace".
- **`VerifyEmailUseCase`**: busca usuario + token por email; si el
  usuario no existe, el token no existe, expiró, o no coincide →
  `InvalidEmailVerificationToken` (audita fallo). Si es válido:
  `user->activate(now)` (fija `status = Active` y `emailVerifiedAt`,
  cumple "Registro de fecha de verificación"), guarda, borra el token
  usado, audita (`auth.email_verified`, éxito).
- `RegisterUserUseCase` gana `SendEmailVerificationUseCase` como cuarta
  dependencia del constructor, invocada tras `$this->users->save($user)`.
  `ImportUsersJob::importRow()` se actualiza para inyectarla también vía
  parámetro de `handle()` — los usuarios importados en lote también
  necesitan verificar su correo antes de poder iniciar sesión (sin este
  cambio quedarían permanentemente bloqueados, ya que hoy nada más los
  activa).

### Infrastructure (`modules/Identity/Infrastructure/`)

- **Nueva migración** `create_email_verification_tokens_table` (`email`
  PK, `token`, `created_at`) — mismo esquema que `password_reset_tokens`,
  tabla separada (no se reutiliza la de recuperación de contraseña, para
  no mezclar propósitos de seguridad distintos).
- `EmailVerificationTokenModel`/`EmailVerificationTokenMapper`/
  `EloquentEmailVerificationTokenRepository` — mismo patrón que sus
  equivalentes de `PasswordResetToken`.

### Presentation (`modules/Identity/Presentation/Http/`)

- Se **elimina** la ruta pública
  `POST /api/v1/auth/users/{userId}/activate` (y el `RateLimiter::for('activate', ...)`
  que solo ella usaba — confirmado que la ruta administrativa no lo
  usa). El controlador `ActivateUserController` se mantiene intacto,
  sigue sirviendo la ruta administrativa.
- **`POST /api/v1/auth/verify-email`** (público, `email` + `token`):
  `VerifyEmailRequest`/`VerifyEmailController`. Éxito → 200;
  `InvalidEmailVerificationToken` se resuelve automáticamente vía el
  renderer global de `DomainException` (422).
- **`POST /api/v1/auth/resend-verification`** (público, `email`):
  `ResendEmailVerificationRequest`/`ResendEmailVerificationController`,
  siempre responde el mismo mensaje genérico.
- Dos `RateLimiter` nuevos: `verify-email` (10/min por ip, mismo estilo
  que `reset-password`) y `resend-verification` (5/min por email+ip,
  mismo estilo que `forgot-password`).
- `RateLimitingTest.php` pierde el test de "activaciones públicas" (ruta
  eliminada) y gana equivalentes para los dos endpoints nuevos.

## Fuera de alcance (documentado explícitamente)

- Middleware `EnsureEmailIsVerified` y su aplicación a endpoints — no
  hay una lista concreta de acciones a proteger; el login ya es el gate
  real.
- Enlace clicable a un frontend (mismo motivo que ENG-009: no existe
  `FRONTEND_URL`).
- Cualquier cambio a la ruta administrativa de activación
  (`/api/v1/users/{userId}/activate`, protegida por permiso) — se
  mantiene intacta.
- Limpieza de tokens vencidos vía scheduler (igual que ENG-009: un solo
  token activo por usuario, se reemplaza en cada nueva solicitud).

## Plan de verificación

TDD por capa: Domain → Application → Infrastructure → Presentation. Pint
y PHPStan (`--memory-limit=512M`) tras cada capa y sobre el repo completo
al final. Suite de `Modules\Identity` completa (incluye `RateLimitingTest`,
`BulkImportUsersTest`, `AdminUserManagementTest`, `DateOfBirthRegistrationTest`
— todas dependen indirectamente de `RegisterUserUseCase`) corrida vía
`./vendor/bin/pest` para confirmar ausencia de regresiones tras el cambio
de su constructor.
