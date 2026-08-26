# ENG-044 — Consulta pública controlada (Diseño)

## 1. Objetivo

Permitir que cualquier tercero (sin autenticación) verifique la autenticidad de un certificado emitido por `Modules\Certification` a partir de su código de validación, exponiendo únicamente los datos necesarios para confirmar la validez — no el registro interno completo.

## 2. Alcance acordado con el usuario

**Datos expuestos:** además del mínimo (curso, vigencia efectiva, fecha de emisión, fecha de vigencia), se incluye el **nombre del titular** — útil para que un verificador externo confirme a quién pertenece el certificado. No se expone `user_id`, correo, historial de estados ni el `id` interno del certificado (el código de validación es el identificador público).

**Cálculo de vigencia efectiva (bullet "Vigencia"):** el estado público no es el `status` interno crudo, sino uno calculado — `valid` si `status = Issued` y (`expiresAt` es nulo o futuro); `expired` si `status = Issued` pero `expiresAt` ya pasó; `revoked` si `status = Revoked` (la revocación tiene prioridad sobre la vigencia). Hoy `CertificateResponse` expone el `status` crudo sin considerar `expiresAt`; este cálculo es nuevo y vive en el agregado (`Certificate::effectiveStatus()`), no en la respuesta, porque es una regla de dominio.

**Diferido explícitamente:** listar o enumerar certificados públicamente (solo consulta puntual por código exacto); límite de tasa/anti-abuso del endpoint público (fuera de alcance de este incremento, es una preocupación de infraestructura/gateway); exponer la evidencia del Pasaporte Vial que respalda el certificado (bullet "Evidencia verificable" se interpreta como que la respuesta misma es la prueba verificable de autenticidad, no como exponer evidencia cruzada de otro módulo).

## 3. Dominio

- `CertificateEffectiveStatus` (enum): `Valid`, `Expired`, `Revoked`.
- `Certificate::effectiveStatus(DateTimeImmutable $now): CertificateEffectiveStatus` — método puro en el agregado existente, sin nuevas dependencias.

## 4. Persistencia

Nuevo método `CertificateRepository::findByValidationCode(ValidationCode $code): ?Certificate`, análogo a `findByUserAndCourse`, implementado en `EloquentCertificateRepository` filtrando por la columna única `validation_code`.

## 5. Aplicación (CQRS)

- `VerifyCertificateQuery(string $validationCode)` → `VerifyCertificateHandler`.
- El handler depende directamente de `Modules\Identity\Domain\Repositories\UserRepository` y `Modules\Academic\Domain\Repositories\CourseRepository` (mismo precedente que `AssignRoleHandler` en `Authorization`, que depende de `UserRepository` de `Identity`) — sin crear una interfaz de resolución nueva, porque no hay ningún caso de uso reactivo/opcional aquí: la verificación pública necesita el nombre y el curso siempre, no es un enriquecimiento best-effort.
- Un código con formato inválido (`ValidationCode::fromString` lanza `InvalidArgumentException`) o inexistente responde igual: `CertificateNotFound` (404) — no se distingue el motivo, para no dar pistas sobre qué códigos son "casi válidos".
- `CertificateVerificationResponse` (DTO público): `validation_code`, `status` (efectivo), `issued_at`, `expires_at`, `course_id`, `course_name`, `holder_name`.

## 6. HTTP

Público (sin `auth:sanctum`), bajo el prefijo existente `api/v1/certification`:

- `GET /certification/verify/{validationCode}` — sin autenticación, sin permiso. Error público: `CERTIFICATE_NOT_FOUND` (404, mismo código ya existente).
