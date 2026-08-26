# ENG-052 — Insignias: alcance acordado

Segunda historia de la Fase 10 — Gamificación. Extiende `Modules\Gamification` (creado en ENG-051) con un segundo agregado, `Badge`, distinto de `Achievement` en tres puntos: tiene categoría cerrada, nivel fijo y contenido versionado editable.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Otorgamiento**: manual vía `badges.manage`, igual que `Achievement` (ENG-051) y `Certificate` (ENG-043). Sin motor de evaluación automática de reglas.
2. **Niveles**: atributo fijo de la insignia (`BadgeLevel`: `Bronze`/`Silver`/`Gold`), asignado al crearla o editarla. Sin sistema de progresión ni acumulación — eso queda para ENG-053 (Experiencia y niveles), que es un concepto distinto (nivel del usuario, no de la insignia).
3. **Versionado**: campo `version` (entero, inicia en 1) que se incrementa al editar el contenido de la insignia (nombre, descripción, criterio, categoría o nivel). El otorgamiento a un usuario (`UserBadge`) guarda `awardedVersion` — la versión vigente en el momento de otorgarse — pero no se conservan snapshots históricos completos del contenido anterior (a diferencia de `CourseVersion` en Academic).
4. **Categoría**: enum cerrado `BadgeCategory`: `Educational`/`Institutional`/`Practical`, tal como los enumera el roadmap.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Edición solo si `Active`**: `updateContent()` se rechaza si la insignia está `Retired` (mismo criterio de "no hay reversión" que `Achievement::retire()`), mediante una excepción de dominio nueva `InvalidBadgeTransition` (422) — reutilizada tanto para "retirar dos veces" como para "editar una insignia retirada".
- **`criteria` en vez de `earningRule`**: mismo concepto que `Achievement::earningRule()` (texto libre descriptivo), pero nombrado `criteria` para diferenciarlo en la documentación de API sin implicar comportamiento distinto.
- **`registeredAt` (no `createdAt`)**: mismo criterio que `Achievement`/`Simulator`, para evitar colisión con las columnas de auditoría automáticas de Eloquent.
- **`UserBadge` inmutable de solo-append**: mismo patrón que `UserAchievement` — sin revocación (diferida, igual que en ENG-051).
- **Permisos nuevos `badges.manage`/`badges.view`** (no se reutilizan `achievements.*`, porque son catálogos independientes). `badges.view` se otorga también a `Student`, mismo criterio que `achievements.view` (catálogo de navegación abierta y motivacional, no un registro personal).
- **HTTP**: `PUT /badges/{badgeId}` para la edición de contenido (mismo verbo que `QuestionController::update`/`ExamController::update` en Academic), separado de `POST /badges/{badgeId}/retire` y `POST /badges/{badgeId}/grant`.

## Incluye (del roadmap)

- Insignias educativas.
- Insignias institucionales.
- Insignias prácticas.
- Niveles.
- Versionado.

## Diferido explícitamente

- Evaluación automática de reglas de otorgamiento (otorgamiento manual, igual que ENG-051).
- Sistema de progresión de niveles por acumulación de insignias (corresponde a ENG-053).
- Historial completo de snapshots por versión (solo se conserva el número de versión, no el contenido histórico).
- Revocación de una insignia ya otorgada.
- Consulta de las insignias obtenidas por otro usuario (solo autoservicio vía `/badges/me`).
