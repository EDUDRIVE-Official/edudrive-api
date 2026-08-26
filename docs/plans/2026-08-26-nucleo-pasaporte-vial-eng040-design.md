# ENG-040 — Núcleo del Pasaporte Vial (Diseño)

## 1. Objetivo

Establecer la identidad y el ciclo de vida del Pasaporte Vial: un agregado por persona (`RoadPassport`) con estado, nivel e historial propio de cambios. Es la base sobre la que ENG-041 (Evidencias) y ENG-042 (Competency Trust Model) construirán la agregación real de cursos/evaluaciones/prácticas y el cálculo de confianza.

## 2. Alcance acordado con el usuario

**Incluido:** identificador propio, propietario (`userId`), estado (`active`/`suspended`/`revoked`), nivel numérico (entero ≥ 1, solo puede subir mientras el pasaporte está activo), e historial propio de cambios de estado y nivel (no la lista de cursos/evaluaciones — eso es ENG-041).

**Diferido explícitamente:** agregación de evidencias (cursos, evaluaciones, prácticas, simulaciones, certificaciones — ENG-041); cálculo de confianza/nivel automático a partir de evidencia (ENG-042); reemisión de un pasaporte revocado (fuera de alcance, decisión futura); topes de negocio sobre el nivel máximo.

## 3. Módulo nuevo

`Modules\RoadPassport`, siguiendo `docs/engineering/ENG-003-estandar-modulos-backend.md` al pie de la letra (mismo patrón que `Modules\Learning` en ENG-038): capas Domain/Application/Infrastructure/Presentation, `RoadPassportServiceProvider`, endpoint de estado inicial `GET /api/v1/road-passport/status`, registrado en `bootstrap/providers.php`.

No se accede a modelos internos de `Identity` — igual que `Enrollment`/`LearningEvent`, `userId` se guarda como `string` sin FK entre módulos.

## 4. Dominio

- `RoadPassportId` (VO, UUID) — mismo patrón que `EnrollmentId`.
- `RoadPassportStatus` (enum): `Active`, `Suspended`, `Revoked`.
- `PassportHistoryEntry` (VO inmutable): `type` (`RoadPassportHistoryType`: `StatusChanged`/`LevelChanged`), `fromValue: string`, `toValue: string`, `occurredAt: DateTimeImmutable`, `reason: ?string`. Un solo tipo de entrada sirve para ambos casos de historial (el valor se serializa como string: `"active"`/`"3"`).
- Agregado `RoadPassport`: `id`, `userId`, `status`, `level` (int), `issuedAt`, `history` (`list<PassportHistoryEntry>`).
  - `create(id, userId, issuedAt)`: `status = Active`, `level = 1`, historial vacío.
  - `suspend(?reason, at)`: solo desde `Active`; agrega entrada de historial. Rechaza si no está `Active` (`InvalidRoadPassportTransition`).
  - `reactivate(at)`: solo desde `Suspended` → `Active`; agrega entrada de historial. Rechaza si no está `Suspended`.
  - `revoke(?reason, at)`: desde `Active` o `Suspended` → `Revoked` (terminal); agrega entrada de historial. Rechaza si ya está `Revoked`.
  - `changeLevel(newLevel, at)`: solo si `status === Active`; `newLevel` debe ser un entero mayor al nivel actual (`InvalidRoadPassportLevel` si no sube o no es positivo). Agrega entrada de historial.
  - `restore(...)`: reconstrucción completa desde persistencia, igual que el resto de agregados del proyecto.
- Excepciones de dominio: `InvalidRoadPassportTransition`, `InvalidRoadPassportLevel` (extienden `Modules\Foundation\Domain\Exceptions\DomainException`).
- `RoadPassportRepository` (contrato): `save`, `findById`, `findByUserId`.

## 5. Persistencia

Dos tablas nuevas, PK UUID, con el prefijo del módulo:

- `road_passports`: `id`, `user_id` (único — un pasaporte por persona), `status`, `level`, `issued_at`, timestamps.
- `road_passport_history_entries`: `id`, `road_passport_id` (FK cascada a `road_passports`), `type`, `from_value`, `to_value`, `reason` (nullable), `occurred_at`.

`RoadPassportModel`/`RoadPassportHistoryEntryModel` + `EloquentRoadPassportRepository` (guarda el agregado y su historial en una transacción, igual que `EloquentExamRepository` con sus preguntas).

## 6. Aplicación (CQRS)

- `IssueRoadPassportCommand(userId)` → `IssueRoadPassportHandler`. Rechaza si el usuario ya tiene un pasaporte (`RoadPassportAlreadyExists`, 409), sin importar su estado (reemisión tras revocación queda fuera de alcance).
- `SuspendRoadPassportCommand(roadPassportId, ?reason)`, `ReactivateRoadPassportCommand(roadPassportId)`, `RevokeRoadPassportCommand(roadPassportId, ?reason)`, `ChangeRoadPassportLevelCommand(roadPassportId, level)` → un handler cada uno, todos con `RoadPassportNotFound` (404) si no existe.
- `GetRoadPassportQuery(roadPassportId, userId, canViewOthers)` → `GetRoadPassportHandler`, mismo patrón de autorización que `GetEnrollmentProgressHandler` (dueño o permiso ampliado).
- `GetMyRoadPassportQuery(userId)` → `GetMyRoadPassportHandler`, resuelve el pasaporte del usuario autenticado por `userId` sin necesitar conocer su `roadPassportId` (caso de uso principal para un estudiante). Sin autorización adicional: siempre es "propio".
- `RoadPassportResponse` (DTO): `id`, `user_id`, `status`, `level`, `issued_at`, `history` (lista de `{type, from, to, occurred_at, reason}`).

## 7. Autorización

Nuevos permisos `road_passports.manage` (emitir/suspender/reactivar/revocar/cambiar nivel) y `road_passports.view` (consultar el pasaporte de un tercero), mismo patrón exacto que `enrollments.manage`/`enrollments.view`:

- `SuperAdmin`: ambos.
- `InstitutionalAdmin`: ambos.
- `Teacher`: solo `road_passports.view`.
- `Student`: ninguno (acceso a su propio pasaporte por pertenencia, vía `GetMyRoadPassportQuery` o por dueño en `GetRoadPassportQuery`).

## 8. HTTP

Bajo `auth:sanctum`, prefijo `api/v1/road-passport`:

- `GET /road-passport/status` — endpoint de estado inicial (ENG-003 §18), sin autenticación (igual que `academic/status`).
- `POST /road-passport` (body: `user_id`) — `road_passports.manage`.
- `GET /road-passport/me` — cualquier usuario autenticado, resuelve el propio.
- `GET /road-passport/{roadPassportId}` — dueño o `road_passports.view`.
- `POST /road-passport/{roadPassportId}/suspend`, `/reactivate`, `/revoke` (body opcional `reason`) — `road_passports.manage`.
- `PUT /road-passport/{roadPassportId}/level` (body: `level`) — `road_passports.manage`.

Errores públicos: `ROAD_PASSPORT_NOT_FOUND` (404), `ROAD_PASSPORT_ALREADY_EXISTS` (409), `INVALID_ROAD_PASSPORT_TRANSITION` (422), `INVALID_ROAD_PASSPORT_LEVEL` (422).
