# ENG-053 — Experiencia y niveles: alcance acordado

Tercera historia de la Fase 10 — Gamificación. Extiende `Modules\Gamification` con un tercer concepto, `ExperienceEntry`, distinto de `Achievement`/`Badge` en que no es un catálogo: es un ledger de solo-append de puntos de experiencia (XP), del cual se derivan niveles mediante un servicio de dominio puro.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Otorgamiento**: manual vía `experience.manage`, mismo criterio que `Achievement` (ENG-051) y `Badge` (ENG-052). Sin integración automática reactiva con otros módulos (logros, insignias, cursos, exámenes).
2. **Cálculo de nivel**: servicio de dominio puro `ExperienceLevelCalculator`, calculado en cada consulta a partir de la suma de puntos acumulados — mismo patrón que `PracticalResultCalculator`/`DecisionEngineCalculator`/`RoadPassportTrustCalculator`. El nivel no se persiste.
3. **Regla de progresión**: fórmula fija con umbral uniforme — `nivel = floor(xp_total / 100) + 1` — mismo umbral para el nivel general y para cada nivel por competencia. Sin tabla de umbrales configurable.
4. **Prevención de manipulación**: `ExperienceEntry` es un ledger inmutable de solo-append (sin edición ni borrado, mismo patrón que `UserAchievement`/`UserBadge`/`TelemetryEvent`), exige `points` estrictamente positivo, y solo se registra vía `experience.manage` — un estudiante nunca puede otorgarse XP a sí mismo (no hay autoservicio de registro, solo de consulta).

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Consulta de otro usuario diferida**: siguiendo el mismo criterio que `GetMyAchievementsQuery`/`GetMyBadgesQuery` (ENG-051/052), solo existe autoservicio (`GET /experience/me`, `auth:sanctum` sin permiso adicional). Un administrador consultando el resumen de experiencia de otro usuario queda diferido explícitamente — no hay `experience.view`.
- **`competencyId` en texto libre**: igual que `Achievement::earningRule()`/`Badge::criteria()`, sin referencia real a `Competency` de Academic (mismo criterio ya aplicado en ENG-048/049). Es opcional por registro — si se omite, el punto solo cuenta para el nivel general, no para ningún nivel por competencia.
- **Nivel general vs. nivel por competencia**: el nivel general se deriva de la suma de **todos** los registros del usuario (con o sin `competencyId`); el nivel por competencia se deriva de la suma de los registros que comparten un mismo `competencyId`. Ambos usan la misma fórmula de progresión.
- **Instanciación directa del calculador**: mismo patrón que `GetPracticalResultHandler` (`new PracticalResultCalculator`) — `ExperienceLevelCalculator` se instancia directamente en el handler, sin inyección ni registro en el contenedor.
- **Un solo permiso nuevo, `experience.manage`** (SuperAdmin + InstitutionalAdmin, mismo criterio que `achievements.manage`/`badges.manage`). No se crea `experience.view` (ver punto anterior).

## Incluye (del roadmap)

- Puntos de experiencia.
- Nivel general.
- Nivel por competencia.
- Reglas de progresión.
- Prevención de manipulación.

## Diferido explícitamente

- Integración automática reactiva con otros módulos (logros, insignias, cursos, exámenes) como fuente de XP.
- Tabla de umbrales de progresión configurable por nivel (curva personalizada).
- Consulta del resumen de experiencia de otro usuario (solo autoservicio vía `/experience/me`).
- Edición o borrado de un registro de experiencia ya creado (ledger inmutable).
- Referencias reales a `Competency` de Academic.
