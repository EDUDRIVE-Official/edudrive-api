# ENG-022 + ENG-023 — Tutores/encargados y Consentimientos: diseño

**Fase:** 4 — Perfiles educativos.
**Alcance acordado:** solo vista de progreso bajo demanda para el tutor
(sin notificaciones automáticas); relación tutor-menor administrada por
un administrador (sin invitación/verificación real de identidad, mismo
criterio ya usado en ENG-071); revocación de consentimiento agregada al
módulo `Legal` ya existente — todo recomendado y elegido por el usuario.

Se diseñan e implementan juntas por su fuerte acoplamiento (ENG-022
incluye "Consentimientos" como viñeta, resuelta enteramente por
`Modules\Legal`), pero se cierran como dos historias separadas en el
roadmap/ENG-LOG.

## Contexto y hallazgos de la investigación

Un agente en background confirmó que `Modules\Legal` (construido en
ENG-070/071, antes de esta sesión) ya cubre 5 de las 6 viñetas de
ENG-023:

- **Consentimiento informado / Tratamiento de datos**: `ConsentPolicy`
  (versión + fecha de vigencia) + `UserConsent::accept()` +
  `POST /api/v1/legal/consents`.
- **Consentimiento parental**: `UserConsent.guardianDeclaration` —
  autodeclaración de texto libre cuando `User::isMinor()`, sin
  verificación real de identidad de un tutor (decisión ya tomada
  explícitamente en ENG-071).
- **Historial de aceptación**: modelo de acumulación (cada `accept()`
  inserta un registro nuevo, nunca sobrescribe), `GET /api/v1/legal/me/consents`
  ya devuelve todo el historial.
- **Versionado de términos**: `ConsentPolicy` versiona incrementalmente
  por `PolicyKey`. El contenido/texto legal en sí no se almacena — ya
  diferido explícitamente en ENG-070 ("sin editor de texto enriquecido").
- **Revocación de consentimiento**: **no existe**. Es el único gap real
  de ENG-023.

Para ENG-022, la investigación confirmó que **no existe ningún
concepto de relación tutor-estudiante** (ninguna entidad, tabla, rol ni
permiso) — es terreno nuevo. El patrón más cercano reutilizable es
`ExamAttemptController` (un `user_id` opcional + verificación de
permiso), pero no resuelve un vínculo 1:1 tutor-menor. `Role` solo
tiene `SuperAdmin`/`InstitutionalAdmin`/`Teacher`/`Student` — no hay rol
tutor.

## Decisión de diseño

### A. Revocación de consentimiento (`Modules\Legal`, cierra el gap de ENG-023)

- `UserConsent` gana `revokedAt` (`?DateTimeImmutable`), método
  `revoke(DateTimeImmutable $at): void` (lanza
  `Modules\Legal\Domain\Exceptions\ConsentAlreadyRevoked` si ya estaba
  revocado), getters `revokedAt()`/`isRevoked()`.
- `UserConsentRepository` gana
  `findLatestActiveByUserAndPolicy(string $userId, PolicyKey $key): ?UserConsent`
  (el más reciente por `accepted_at` con `revoked_at IS NULL`).
- Nueva migración: columna `revoked_at` (timestamp nullable) en
  `legal_user_consents`.
- `RevokeConsentCommand`/`RevokeConsentHandler`: busca el consentimiento
  activo más reciente para (usuario autenticado, policyKey); si no
  existe, `Modules\Legal\Application\Exceptions\ConsentNotFound` (404);
  si existe, `revoke(now)` y guarda.
- **`DELETE /api/v1/legal/consents/{policyKey}`** (`auth:sanctum`,
  autoservicio — el usuario revoca su propio consentimiento, sin
  permiso adicional). `ConsentController` gana `destroy()`.
- `ConsentResponse` gana `revokedAt` (nullable) para reflejarlo en el
  historial ya expuesto por `GET /me/consents`.

### B. Relación tutor-menor (`Modules\Identity`, nueva, cierra el núcleo de ENG-022)

- **`Entities/GuardianRelationship.php`**: `id`, `guardianUserId`,
  `minorUserId`, `createdAt`, `revokedAt` (`?DateTimeImmutable`).
  Invariante estructural (sin cruzar módulos): `guardianUserId !==
  minorUserId`. `revoke(DateTimeImmutable $at)`, `isActive(): bool`.
- **`Repositories/GuardianRelationshipRepository.php`**: `save()`,
  `findById()`, `findActiveByGuardianAndMinor(guardianId, minorId): ?GuardianRelationship`,
  `findActiveByGuardian(guardianId): list<GuardianRelationship>`.
- **`CreateGuardianRelationshipHandler`**: valida que ambos usuarios
  existan (`UserNotFound`), que el menor sea realmente menor
  (`User::isMinor()`, si no
  `GuardianRelationshipRequiresMinor`), y que no exista ya una relación
  activa para ese par (`GuardianRelationshipAlreadyExists`) — mismo
  principio de deduplicación que `AssignRoleHandler`.
- **`RevokeGuardianRelationshipHandler`**: busca por id
  (`GuardianRelationshipNotFound` si no existe), revoca.
- **Reuso de la composición de perfil ya construida en ENG-020**: se
  extrae `GetMyStudentProfileHandler`'s cuerpo a un nuevo servicio
  `Modules\Identity\Application\Services\StudentProfileComposer::compose(string $userId)`,
  reutilizado por el propio `GetMyStudentProfileHandler` (delegación
  fina) y por el nuevo `GetLinkedMinorProgressHandler` — evita duplicar
  la lógica de agregación (User + StudentProfile + RoadPassport +
  Enrollment).
- **`GetLinkedMinorProgressHandler`**: valida que exista una relación
  activa entre el tutor autenticado y el `minorUserId` solicitado
  (`GuardianRelationshipNotFound`, 404, si no — mismo código sin
  distinguir "no existe" de "no te pertenece"); si existe, delega en
  `StudentProfileComposer`. **Esta es la única restricción de
  privacidad construida**: un tutor solo puede ver menores con quienes
  tiene una relación activa, nada más.
- **`ListMyLinkedMinorsHandler`**: lista los menores vinculados al
  tutor autenticado (id + nombre), para que descubra a quién puede
  consultar antes de pedir el detalle.
- Nueva tabla `guardian_relationships` (FK reales a `users` en ambas
  columnas, `cascadeOnDelete`).
- Nuevo permiso `Permission::ManageGuardianRelationships`
  (`guardian_relationships.manage`): `SuperAdmin`/`InstitutionalAdmin` —
  crear/revocar relaciones es una acción administrativa (mismo criterio
  que ENG-071: sin invitación ni autoservicio del tutor).
- Endpoints:
  - `POST /api/v1/guardians/relationships` (`permission:guardian_relationships.manage`).
  - `DELETE /api/v1/guardians/relationships/{relationshipId}` (mismo permiso).
  - `GET /api/v1/guardians/me/minors` (`auth:sanctum`, autoservicio del tutor).
  - `GET /api/v1/guardians/me/minors/{minorUserId}/progress` (`auth:sanctum`,
    autoservicio, la relación misma es el control de acceso).

## Fuera de alcance (documentado explícitamente)

- Notificaciones automáticas al tutor (eventos de exámenes,
  certificados, etc.) — expandiría el alcance a enganchar
  `Modules\Notification` en varios módulos que hoy no disparan ningún
  evento observable externamente.
- Invitación/autoservicio del tutor para vincularse a un menor, o
  verificación real de su identidad — mismo criterio ya establecido en
  ENG-071.
- Nuevo rol "Tutor" en `Authorization\Domain\Enums\Role` — la relación
  vive como una entidad propia en `Identity`, no como un rol del
  sistema de permisos.
- Contenido/texto de las políticas legales (`ConsentPolicy` sigue sin
  almacenar el texto en sí) — ya diferido en ENG-070.

## Plan de verificación

TDD por capa para ambas partes. Pint y PHPStan
(`--memory-limit=512M`) tras cada capa y sobre el repo completo al
final. Suite de `Modules\Legal` e `Modules\Identity` completas vía
`./vendor/bin/pest`.
