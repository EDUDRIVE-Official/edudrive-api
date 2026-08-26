# ENG-054 — Retos y misiones: alcance acordado

Cuarta historia de la Fase 10 — Gamificación. Extiende `Modules\Gamification` con un cuarto agregado, `Challenge`, y una entidad de seguimiento con lifecycle propio, `ChallengeParticipation` — distinta de `UserAchievement`/`UserBadge` en que no es un registro de solo-append inmutable: tiene una transición `Joined` → `Completed`.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Retos individuales, grupales y misiones educativas**: un solo agregado `Challenge` con un enum cerrado `ChallengeType` (`individual`/`group`/`educational`). Un reto "grupal" es simplemente uno en el que participan varios usuarios, cada uno con su propio registro de `ChallengeParticipation` — sin modelar un concepto nuevo de equipo/grupo con membresía propia ni seguimiento agregado a nivel de grupo.
2. **Participación (unirse) y finalización**: todo manual vía `challenges.manage`, mismo criterio que `Achievement`/`Badge`. Un administrador/instructor registra tanto la unión (`ChallengeParticipation::join()`) como la finalización (`ChallengeParticipation::complete()`). Sin autoservicio de "unirse" por parte del estudiante.
3. **Fechas de vigencia**: restringen funcionalmente la participación — solo se puede registrar una unión nueva si la fecha actual está dentro de la ventana `[startsAt, endsAt]` del reto/misión (`Challenge::isWithinWindow()`).
4. **Recompensa**: campo de texto libre descriptivo (`reward`), mismo criterio que `Achievement::earningRule()`/`Badge::criteria()` — sin vincularse ni otorgar automáticamente un `Achievement`/`Badge` real al completarse.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **`ChallengeParticipation` no es un registro de solo-append inmutable**: a diferencia de `UserAchievement`/`UserBadge`, tiene una transición de estado propia (`Joined` → `Completed`), similar en espíritu a como `Achievement`/`Badge` transicionan `Active` → `Retired` — un método mutador con guarda de invariante (`InvalidChallengeParticipationTransition`, 422, si ya está `Completed`).
- **Invariante de fechas en la creación**: `Challenge::create()`/`restore()` valida que `endsAt` sea posterior a `startsAt` (`InvalidArgumentException`), mismo patrón que `CourseVersion::normalizeVersionNumber()`.
- **Ciclo de vida del catálogo**: `Challenge` reutiliza el mismo patrón `Active`/`Retired` sin reversión que `Achievement`/`Badge` (`InvalidChallengeTransition`, 422, al retirar dos veces). Solo se puede registrar una participación nueva si el reto está `Active` y dentro de su ventana de fechas (`ChallengeNotAvailable`, 422).
- **Permisos nuevos `challenges.manage`/`challenges.view`**, mismo criterio que `achievements.*`/`badges.*` — `challenges.view` se otorga también a `Student` (catálogo de navegación abierta).
- **Autoservicio de consulta únicamente**: `GET /challenges/me` (participaciones propias), mismo criterio que `/achievements/me`/`/badges/me`/`/experience/me` — consultar las participaciones de otro usuario queda diferido.

## Incluye (del roadmap)

- Retos individuales.
- Retos grupales.
- Misiones educativas.
- Fechas.
- Recompensas.
- Seguimiento.

## Diferido explícitamente

- Concepto real de equipo/grupo con membresía propia y seguimiento agregado a nivel de grupo.
- Autoservicio de "unirse" a un reto/misión por parte del estudiante.
- Otorgamiento automático de un `Achievement`/`Badge` real al completar un reto/misión.
- Consulta de las participaciones de otro usuario (solo autoservicio).
- Reversión de una participación ya completada.
