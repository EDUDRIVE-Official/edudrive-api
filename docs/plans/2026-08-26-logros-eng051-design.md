# ENG-051 — Logros (Diseño)

## 1. Objetivo

Un catálogo de logros ("achievements") que documenta qué se puede lograr en EDUDRIVE, y el registro de qué usuario obtuvo cuál logro, cuándo y con qué evidencia — primera historia de la Fase 10 (Gamificación).

## 2. Alcance acordado con el usuario

**Otorgamiento manual:** un administrador otorga el logro a un usuario indicando la evidencia que lo justifica (permiso `achievements.manage`), mismo patrón que la emisión manual de certificados en ENG-043. Las "reglas de obtención" del catálogo son texto descriptivo — documentan el criterio para humanos, no son lógica ejecutable. **Diferido explícitamente:** evaluación automática disparada por eventos de otros módulos (completar cursos, aprobar exámenes, sesiones prácticas sin errores, etc.) — mismo criterio que ENG-043 dejó fuera la emisión automática de certificados desde el Pasaporte Vial.

**"Estado" es el ciclo de vida de la definición en el catálogo:** `Active`/`Retired` (terminal, sin reactivación — mismo espíritu que `Certificate::revoke()`). Un logro retirado ya no puede otorgarse a nadie nuevo, pero quienes ya lo obtuvieron lo conservan. No existe un estado "Locked/Unlocked" por usuario: si un usuario no tiene el logro, simplemente no existe un registro `UserAchievement` para esa combinación — no se pre-crean filas vacías.

**Evidencia como texto libre:** quien otorga el logro escribe una descripción (ej. "Completó el curso de manejo defensivo con 95% de aciertos") — mismo criterio que las evidencias de ENG-048 (texto/datos propios, sin referencias cruzadas obligatorias a registros de otros módulos).

**Un usuario no puede obtener el mismo logro dos veces** (restricción única `achievement_id`+`user_id`).

**Catálogo visible para todos, otorgamiento restringido:** a diferencia de `RoadPassport`/`Certificate` (registros personales), el catálogo de logros es explícitamente motivacional y debe ser visible para cualquier usuario autenticado — mismo espíritu que el catálogo de cursos (`courses.view` se otorga a todos los roles, incluyendo `Student`). Por eso `achievements.view` se concede a los cuatro roles; `achievements.manage` (crear, retirar, otorgar) se concede a `SuperAdmin`+`InstitutionalAdmin`, mismo patrón que `road_passports.manage`/`certifications.manage`, porque otorgar un logro a un estudiante específico es una acción operativa del día a día institucional, no una decisión curricular de alcance de plataforma.

**Diferido explícitamente:** revocar un logro otorgado por error (no solicitado; se puede agregar después si hace falta); consultar el listado de logros obtenidos de un usuario específico distinto al propio (`/me`) — solo se expone la vista de "mis logros"; evaluación automática de reglas.

## 3. Módulo nuevo

`Modules\Gamification`, siguiendo ENG-003 al pie de la letra: capas Domain/Application/Infrastructure/Presentation, `GamificationServiceProvider`, endpoint de estado `GET /api/v1/gamification/status`, registrado en `bootstrap/providers.php`.

## 4. Dominio

- `AchievementId` (VO, UUID) — mismo patrón que `SimulatorId`/`RoadPassportId`.
- `AchievementStatus` (enum): `Active`, `Retired`.
- `InvalidAchievementTransition` (excepción de dominio, 422).
- Agregado `Achievement`: `id`, `code` (único, mismo patrón de validación que `CourseCode`), `name`, `description`, `earningRule` (texto libre), `status`, `createdAt`, `retiredAt` (nullable), `retiredReason` (nullable) — sin lista de historial: solo hay una transición posible (`Active` → `Retired`), así que dos campos nullable son suficientes, no se necesita un VO de historial de una sola entrada.
  - `create(id, code, name, description, earningRule, ?createdAt)`: `status = Active`.
  - `retire(?reason, at)`: rechaza si ya está `Retired` (`InvalidAchievementTransition`).
  - `restore(...)`: reconstrucción completa desde persistencia.
- `UserAchievement` (entidad inmutable, registro de otorgamiento — no es un agregado con transiciones, es un hecho registrado una vez): `id`, `achievementId`, `userId`, `evidence`, `earnedAt`.

## 5. Persistencia

Tablas `achievements` (PK UUID, `code` único) y `user_achievements` (PK UUID, `achievement_id` FK a `achievements`, `user_id` FK a `users`, `evidence`, `earned_at`, único por `achievement_id`+`user_id`). `AchievementRepository`: `save`, `findById`, `findByCode`, `all()`. `UserAchievementRepository`: `save`, `findByAchievementAndUser`, `allForUser`.

## 6. Aplicación (CQRS)

- `CreateAchievementCommand(code, name, description, earningRule)` → `CreateAchievementHandler`. Rechaza `code` duplicado (`AchievementAlreadyExists`, 409).
- `RetireAchievementCommand(achievementId, ?reason)` → `RetireAchievementHandler`. `AchievementNotFound` (404) si no existe.
- `GrantAchievementCommand(achievementId, userId, evidence)` → `GrantAchievementHandler`. Valida que el logro exista y esté `Active` (`AchievementNotAvailable`, 422, si está `Retired`); rechaza si el usuario ya lo tiene (`AchievementAlreadyGranted`, 409).
- `GetAchievementQuery(achievementId)` → `GetAchievementHandler`.
- `ListAchievementsQuery()` → `ListAchievementsHandler`.
- `GetMyAchievementsQuery(userId)` → `GetMyAchievementsHandler` — lista los logros obtenidos por el usuario autenticado.

## 7. Autorización

Permisos nuevos `achievements.manage`/`achievements.view`. `achievements.view` se concede a **los cuatro roles** (`SuperAdmin`, `InstitutionalAdmin`, `Teacher`, `Student`) — el catálogo es motivacional y debe verse, mismo criterio que `courses.view`. `achievements.manage` se concede a `SuperAdmin`+`InstitutionalAdmin`, mismo patrón que `road_passports.manage`/`certifications.manage`.

## 8. HTTP

Bajo `api/v1/gamification`:

- `GET /gamification/status` — sin autenticación (ENG-003 §18).
- `POST /gamification/achievements` (body: `code`, `name`, `description`, `earning_rule`) — `achievements.manage`.
- `GET /gamification/achievements` — `achievements.view`.
- `GET /gamification/achievements/{achievementId}` — `achievements.view`.
- `POST /gamification/achievements/{achievementId}/retire` (body opcional `reason`) — `achievements.manage`.
- `POST /gamification/achievements/{achievementId}/grant` (body: `user_id`, `evidence`) — `achievements.manage`.
- `GET /gamification/achievements/me` — cualquier usuario autenticado, lista los propios logros obtenidos.

Errores públicos: `ACHIEVEMENT_NOT_FOUND` (404), `ACHIEVEMENT_ALREADY_EXISTS` (409), `ACHIEVEMENT_ALREADY_GRANTED` (409), `ACHIEVEMENT_NOT_AVAILABLE` (422), `INVALID_ACHIEVEMENT_TRANSITION` (422).
