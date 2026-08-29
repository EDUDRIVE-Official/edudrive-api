# ENG-009 — Recuperación de contraseña: diseño

**Fase:** 1 — Identidad y acceso.
**Alcance acordado:** mecanismo propio de token (patrón `Identity` ya
establecido), recomendado y elegido por el usuario.

## Contexto y hallazgos de la investigación

Un agente en background confirmó que no existe ningún mecanismo de
recuperación de contraseña en el proyecto (ni rutas, ni casos de uso, ni
value objects). Complementando esa investigación, se confirmó además que
**la tabla `password_reset_tokens` ya existe** —creada de fábrica en
`modules/Identity/Infrastructure/Persistence/Migrations/2026_07_24_000001_create_identity_tables.php:26-30`
(`email` como PK, `token`, `created_at`)— pero nunca se usó: `config/auth.php`
tiene el bloque `passwords.users` configurado por defecto pero jamás se
invoca `Password::sendResetLink`/`Password::reset`, y no hay ninguna otra
referencia a la tabla en el código. Se reutiliza esta tabla existente en
vez de crear una nueva.

- El broker nativo de Laravel (`PasswordBroker` + notificación
  `ResetPassword` vía `$user->notify()`) no es compatible sin adaptación:
  `UserModel` no usa el trait `Notifiable`, y el flujo nativo opera
  directamente sobre Eloquent, incompatible con el patrón de dominio
  (entidad + repositorio explícito) ya establecido en todo `Identity`.
- No existe ningún patrón previo de "token temporal" reutilizable
  (`ActivateUserController` no usa token, solo `userId` en la URL). Se
  construye el mecanismo desde cero, mirando el estilo de
  `AccessTokenIssuer`/`AccessTokenRevoker` (puerto en `Application/Services`,
  implementación en `Infrastructure`).
- El correo real (`Modules\Notification\EmailNotificationSender::send(userId,
  subject, body)`, ENG-081) ya resuelve el email del usuario internamente
  vía `UserRepository` y encola el envío — reutilizable tal cual, sin
  pasar por la entidad `Notification` (evita mezclar un correo transaccional
  de seguridad con el buzón de notificaciones de negocio del usuario).
- No existe ningún `FRONTEND_URL` configurado en el proyecto — el correo
  incluye el token en texto plano con instrucciones, no un enlace
  clicable a un frontend que no está decidido.
- El patrón de "no revelar si un email existe" ya lo usa `LoginUserUseCase`
  (misma excepción para credenciales inválidas y usuario inexistente); se
  replica aquí para `forgot-password`.

## Decisiones de diseño

### Domain (`modules/Identity/Domain/`)

- **`Entities/PasswordResetToken.php`**: `email` (VO `Email`), `tokenHash`
  (string, sha256 del token en texto plano — determinístico para poder
  buscar por igualdad exacta, a diferencia de bcrypt), `createdAt`.
  `issue()`/`reconstitute()` estáticos (mismo estilo que `User`).
  `isExpired(DateTimeImmutable $asOf): bool` con `TTL_MINUTES = 60` como
  invariante de dominio. `matchesHash(string $hash): bool`.
- **`Repositories/PasswordResetTokenRepository.php`**: `save()` (upsert
  por `email`, reemplaza cualquier token previo del mismo correo —
  garantiza un único token activo por usuario), `findByEmail(Email
  $email): ?PasswordResetToken`, `deleteByEmail(Email $email): void`.
- **`Exceptions/InvalidPasswordResetToken.php`**: extiende
  `DomainException` (422, `INVALID_PASSWORD_RESET_TOKEN`) — **un único
  mensaje/código** para token inexistente, expirado o que no coincide,
  igual que `InvalidCredentials` unifica credenciales incorrectas y
  usuario inexistente (evita que un atacante distinga los casos).

### Application (`modules/Identity/Application/`)

- **`RequestPasswordResetUseCase`** (`forgot-password`): busca el usuario
  por email; si no existe, audita el intento (`outcome: failure`, sin
  `userId`) y termina sin enviar correo ni error — el controlador
  siempre responde el mismo mensaje genérico. Si existe: genera un token
  aleatorio (`Str::random(64)`), calcula su hash sha256, guarda
  `PasswordResetToken::issue(...)` (reemplaza cualquier token previo del
  usuario), envía el correo con el token en texto plano vía
  `EmailNotificationSender`, audita (`auth.password_reset_requested`,
  éxito).
- **`ResetPasswordUseCase`** (`reset-password`): busca el usuario por
  email y el token guardado por email; si el usuario no existe, el token
  no existe, está expirado, o su hash no coincide con el token recibido
  → `InvalidPasswordResetToken` (audita fallo). Si es válido: actualiza
  el hash de contraseña del usuario (`changePasswordHash`), borra el
  token usado (`deleteByEmail`), **invalida todas las sesiones
  anteriores** (`AccessTokenRevoker::revokeAllForUser`, cumple la viñeta
  "Invalidación de sesiones anteriores"), audita
  (`auth.password_reset`, éxito).

### Infrastructure (`modules/Identity/Infrastructure/`)

- **`Persistence/Eloquent/Models/PasswordResetTokenModel.php`**: PK
  `email` (string, no incremental), `$timestamps = false` (la tabla solo
  tiene `created_at`, sin `updated_at`).
- **`Persistence/Eloquent/PasswordResetTokenMapper.php`**: mismo patrón
  que `UserMapper` (`toDomain`/`toPersistence`).
- **`Persistence/Repositories/EloquentPasswordResetTokenRepository.php`**:
  `save()` vía `updateOrCreate(['email' => ...], ...)`.
- Ninguna migración nueva — se reutiliza la tabla existente.

### Presentation (`modules/Identity/Presentation/Http/`)

- **`Requests/ForgotPasswordRequest.php`**: `email` requerido, formato
  email.
- **`Requests/ResetPasswordRequest.php`**: `email` requerido, `token`
  requerido, `password` requerido con `confirmed` (mismo patrón que
  registro).
- **`ForgotPasswordController`**: siempre responde 200 con un mensaje
  genérico ("Si el correo existe, se enviará un enlace de recuperación."),
  exista o no el email — nunca revela la diferencia.
- **`ResetPasswordController`**: éxito → 200; `InvalidPasswordResetToken`
  se resuelve automáticamente vía el renderer global de `DomainException`
  en `bootstrap/app.php` (422).
- Rutas nuevas en `modules/Identity/routes/api.php`, fuera del grupo
  `auth:sanctum` (públicas, como login/register):
  `POST /api/v1/auth/forgot-password` (`throttle:forgot-password`),
  `POST /api/v1/auth/reset-password` (`throttle:reset-password`).
- Dos nuevos `RateLimiter::for(...)` en `FoundationServiceProvider`:
  `forgot-password` (5/min, por email+ip, mismo estilo que `login`) y
  `reset-password` (10/min, por ip, mismo estilo que `activate` — aquí no
  hay email fiable antes de validar el payload completo).

## Fuera de alcance (documentado explícitamente)

- Enlace clicable a un frontend (no existe `FRONTEND_URL` decidido en el
  proyecto).
- Cualquier cambio a `UserModel` para agregar `Notifiable` o usar el
  broker nativo de Laravel.
- Expiración/limpieza automática de tokens vencidos vía scheduler (el
  volumen es mínimo — un token por usuario, se reemplaza en cada nueva
  solicitud — no justifica un comando de limpieza dedicado).

## Plan de verificación

TDD por capa: Domain → Application → Infrastructure → Presentation. Pint
y PHPStan (`--memory-limit=512M`) tras cada capa y sobre el repo completo
al final. Suite de `Modules\Identity` corrida vía `./vendor/bin/pest`
(no `artisan test`, por el quirk de memoria ya conocido) para confirmar
ausencia de regresiones.
