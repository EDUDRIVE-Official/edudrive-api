# ENG-043 — Credenciales y certificaciones (Diseño)

## 1. Objetivo

Emitir certificados formales verificables por curso, con un código de validación único, vigencia opcional y revocación — como credencial independiente, no como parte del Pasaporte Vial.

## 2. Alcance acordado con el usuario

**Incluido:** emisión **administrativa/manual** de certificados (permiso `certifications.manage`, mismo patrón que la emisión de `RoadPassport` en ENG-040) para un usuario+curso, con código de validación generado al emitir, vigencia opcional (`expiresAt`), revocación terminal e historial de cambios de estado.

**Diferido explícitamente:** emisión automática disparada por evidencia del Pasaporte Vial (`course_completed`) — se mantienen como conceptos de dominio separados en este incremento; verificación pública por código (bullet propio de ENG-044 — "Verificación mediante código"); reemisión de un certificado revocado (mismo criterio que ENG-040 con el pasaporte).

## 3. Módulo nuevo

`Modules\Certification`, siguiendo ENG-003 al pie de la letra (mismo patrón de bootstrap que `Modules\RoadPassport`/`Modules\Learning`): capas Domain/Application/Infrastructure/Presentation, `CertificationServiceProvider`, endpoint de estado `GET /api/v1/certification/status`, registrado en `bootstrap/providers.php`. `userId`/`courseId` como `string` sin depender de clases internas de otros módulos (FK a nivel de base de datos sí, mismo precedente que `road_passports.user_id` → `users` y `road_passport_evidence.course_id` → `academic_courses`).

## 4. Dominio

- `CertificateId` (VO, UUID) — mismo patrón que `RoadPassportId`.
- `ValidationCode` (VO): código aleatorio de 12 caracteres alfanuméricos en mayúsculas, agrupado en 3 bloques de 4 (`XXXX-XXXX-XXXX`), excluyendo caracteres ambiguos (`0`, `O`, `1`, `I`) para legibilidad humana. `generate()` crea uno nuevo; `fromString()` valida el formato al reconstruir desde persistencia.
- `CertificateStatus` (enum): `Issued`, `Revoked` (terminal — sin `suspended`, a diferencia de `RoadPassport`: un certificado revocado no se reactiva).
- `CertificateHistoryEntry` (VO): `fromStatus`, `toStatus`, `occurredAt`, `reason` (nullable) — mismo espíritu que `PassportHistoryEntry` pero solo para cambios de estado (no hay "nivel" en un certificado).
- Agregado `Certificate`: `id`, `userId`, `courseId`, `validationCode`, `status`, `issuedAt`, `expiresAt` (nullable), `history`.
  - `create(id, userId, courseId, validationCode, ?expiresAt, ?issuedAt)`: `status = Issued`.
  - `revoke(?reason, at)`: solo desde `Issued`; rechaza si ya está `Revoked` (`InvalidCertificateTransition`).
  - `restore(...)`: reconstrucción completa desde persistencia.

## 5. Persistencia

Tablas `certificates` (PK UUID, `user_id` FK a `users`, `course_id` FK a `academic_courses`, `validation_code` único, `status`, `issued_at`, `expires_at` nullable) y `certificate_history_entries` (FK cascada a `certificates`). `EloquentCertificateRepository::save()` transaccional, borra y reinserta el historial completo (mismo patrón que `EloquentRoadPassportRepository`).

## 6. Aplicación (CQRS)

- `IssueCertificateCommand(userId, courseId, ?expiresAt)` → `IssueCertificateHandler`. Rechaza si el usuario ya tiene **cualquier** certificado (emitido o revocado) para ese curso (`CertificateAlreadyExists`, 409) — mismo criterio que `RoadPassport` respecto a reemisión tras revocación: fuera de alcance.
- `RevokeCertificateCommand(certificateId, ?reason)` → `RevokeCertificateHandler`. `CertificateNotFound` (404) si no existe.
- `GetCertificateQuery(certificateId, userId, canViewOthers)` → `GetCertificateHandler`, mismo patrón de autorización que `GetRoadPassportHandler` (dueño o permiso ampliado).
- `GetMyCertificatesQuery(userId)` → `GetMyCertificatesHandler`, lista todos los certificados del usuario autenticado (a diferencia del pasaporte, un usuario puede tener varios certificados — uno por curso).
- `CertificateResponse` (DTO): `id`, `user_id`, `course_id`, `validation_code`, `status`, `issued_at`, `expires_at`, `history`.

## 7. Autorización

Permisos nuevos `certifications.manage`/`certifications.view`, mismo patrón de concesión que `road_passports.manage`/`road_passports.view`: `SuperAdmin` e `InstitutionalAdmin` ambos; `Teacher` solo view; `Student` ninguno (accede a los propios por pertenencia vía `GET /certificates/me`).

## 8. HTTP

Bajo `auth:sanctum`, prefijo `api/v1/certification`:

- `GET /certification/status` — sin autenticación (ENG-003 §18).
- `POST /certification/certificates` (body: `user_id`, `course_id`, `expires_at` opcional) — `certifications.manage`.
- `GET /certification/certificates/me` — cualquier usuario autenticado, lista los propios.
- `GET /certification/certificates/{certificateId}` — dueño o `certifications.view`.
- `POST /certification/certificates/{certificateId}/revoke` (body opcional `reason`) — `certifications.manage`.

Errores públicos: `CERTIFICATE_NOT_FOUND` (404), `CERTIFICATE_ALREADY_EXISTS` (409), `INVALID_CERTIFICATE_TRANSITION` (422).
