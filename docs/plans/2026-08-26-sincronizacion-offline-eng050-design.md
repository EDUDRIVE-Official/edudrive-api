# ENG-050 — Sincronización offline (Diseño)

## 1. Objetivo

Permitir que un simulador que estuvo desconectado sincronice, al reconectarse, la telemetría (ENG-047) y los puntos de decisión (ENG-049) acumulados durante ese periodo, sin duplicar datos si reintenta un envío y sin perder datos genuinos solo porque llegaron tarde.

## 2. Alcance acordado con el usuario

**Rol de EDUDRIVE:** la cola local y el manejo de la desconexión son responsabilidad del simulador (fuera de alcance de este backend, mismo criterio que el catálogo real de vehículos/escenarios en ENG-046). El trabajo de EDUDRIVE es que `POST /sessions/{id}/telemetry` y `POST /sessions/{id}/decisions` (ya construidos) acepten reenvíos de forma idempotente y toleren que los datos lleguen después de que la sesión haya cambiado de estado.

**Identificadores idempotentes:** cada lectura de telemetría, evento de telemetría y punto de decisión ahora incluye su propio `id` (UUID) generado por el simulador, en vez de que EDUDRIVE lo genere con `Str::uuid()` al guardar. Al guardar el lote, se usa `insertOrIgnore()` (Eloquent) en vez de `insert()` — si un `id` ya existe en la base de datos (porque el simulador reintentó un lote ya procesado), esa fila se omite silenciosamente en lugar de fallar o duplicarse. La respuesta de cada envío (`samples_recorded`/`events_recorded`/`decisions_recorded`) refleja cuántas filas se insertaron **realmente** (el conteo que devuelve `insertOrIgnore()`), no cuántas venían en el lote — así el simulador puede distinguir un reenvío completo (0 nuevas) de uno parcial.

**Resolución de conflictos — datos tardíos:** se relaja la validación de "la sesión debe estar `InProgress` en este momento" a "la lectura/evento/decisión debe haber ocurrido durante el periodo en que la sesión estuvo en curso", comparando su marca de tiempo (`recorded_at`/`occurred_at`) contra `startedAt`/`endedAt` de la sesión (nuevo método `SimulationSession::wasInProgressAt(DateTimeImmutable): bool`). Esto acepta datos que llegan después de que la sesión ya se completó (o incluso se canceló por otro canal mientras el simulador estaba desconectado — una sesión cancelada nunca tuvo `startedAt`, porque `cancel()` solo es posible desde `Scheduled`, así que queda excluida automáticamente), siempre que el dato en sí sea genuino (ocurrió dentro de la ventana real de la sesión). Si **cualquier** ítem del lote cae fuera de esa ventana (o la sesión nunca se inició), se rechaza el lote completo — mismo criterio "todo o nada" que ya se usaba para la sesión↔simulador.

**Diferido explícitamente:** modelar la sesión offline como un concepto de dominio propio (reportar una sesión completa programada+iniciada+completada en un solo envío retroactivo) — la sesión sigue programándose/iniciándose/completándose por los canales ya existentes (ENG-046), solo su telemetría/decisiones pueden llegar tarde; una tabla de llaves de idempotencia por lote completo (se prefirió id por ítem, más simple y sin tabla nueva); resolución de conflictos más allá de la ventana temporal (ej. fusionar lecturas contradictorias).

## 3. Módulo

Se extiende `Modules\Simulation` (mismo *bounded context*). No hay entidades ni tablas nuevas — se modifica el comportamiento de `TelemetrySample`/`TelemetryEvent`/`DecisionPoint` (ya existentes) y de sus repositorios.

## 4. Dominio

- `SimulationSession::wasInProgressAt(DateTimeImmutable $at): bool` — método de consulta puro nuevo en el agregado existente: `false` si `startedAt` es nulo (nunca se inició — cubre `Scheduled` y `Cancelled`); `false` si `$at` es anterior a `startedAt`; `false` si `endedAt` no es nulo y `$at` es posterior a `endedAt`; `true` en cualquier otro caso (incluye `InProgress`, donde `endedAt` siempre es nulo, y `Completed` dentro de la ventana real).

## 5. Persistencia

`TelemetrySampleRepository::saveBatch()`, `TelemetryEventRepository::saveBatch()` y `DecisionPointRepository::saveBatch()` cambian su firma de `void` a `int` (filas realmente insertadas) y sus implementaciones Eloquent usan `insertOrIgnore()` en vez de `insert()`.

## 6. Aplicación (CQRS)

- `SubmitTelemetryCommand`/`SubmitDecisionPointsCommand`: cada ítem del arreglo de entrada ahora incluye `id` (UUID, provisto por el simulador).
- `SubmitTelemetryHandler`/`SubmitDecisionPointsHandler`: construyen las entidades con el `id` recibido (no generan uno nuevo); validan que **todos** los ítems del lote satisfagan `session->wasInProgressAt(su marca de tiempo)` antes de guardar nada (si alguno falla, se lanza `SimulationSessionNotInProgress` para el lote completo — mismo código de error ya existente, criterio ampliado); el conteo de la respuesta viene del valor que devuelve `saveBatch()`.

## 7. HTTP

`SubmitTelemetryRequest`/`SubmitDecisionPointsRequest` agregan la regla `required|uuid` para `samples.*.id`/`events.*.id`/`decisions.*.id`. Sin cambios en las rutas ni en los permisos — mismos endpoints de ENG-047/049.
