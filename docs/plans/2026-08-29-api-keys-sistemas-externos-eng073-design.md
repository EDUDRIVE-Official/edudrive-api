# ENG-073 — API Keys para sistemas externos: alcance acordado

Primera historia de la Fase 15 — Integraciones. El roadmap pide seis puntos: Identificación del consumidor, Alcances, Revocación, Expiración, Rate limiting, Auditoría.

## Estado previo encontrado (investigación, no una decisión del usuario)

- No existe ningún mecanismo de API keys para sistemas externos. Lo más parecido es `Modules\Simulation`'s llave de integración de simuladores: `IntegrationKey` (hash SHA-256, `random_bytes(32)`, revelado único en texto plano al crear/rotar, nunca persistido), un ciclo de vida `Active/Suspended/Retired` con historial append-only, y una autenticación por hash-como-clave-de-búsqueda (`AuthenticateSimulator` middleware). Es un patrón probado, pero específico del dominio de simuladores (sin concepto de alcances/scopes — una llave válida da acceso total a los dos endpoints de telemetría/decisiones).
- `Modules\Authorization`'s enum `Permission` es una lista plana de strings `recurso.accion` sin acoplamiento estructural a `Role`/`User` en el propio enum — reutilizable como vocabulario de scopes sin arrastrar el mecanismo de roles humanos.
- El limitador de tasa nombrado `simulator-integration` (ENG-067) ya establece el patrón exacto a replicar: `RateLimiter::for(...)` leyendo un atributo que el middleware de autenticación adjunta a la petición.
- **Restricción importante encontrada**: `audit_logs.user_id` ahora tiene una FK real hacia `users` (agregada al cerrar ENG-072/Fase 14) — un actor que no es un `Modules\Identity` `User` (como un consumidor de API) no puede registrarse en esa columna. Cualquier auditoría de acciones de un consumidor debe dejar `user_id` en `null` y poner el identificador del consumidor en `metadata`.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: se construye el mecanismo completo de API keys (identificación, alcances, expiración, suspensión/reactivación/revocación, rotación, limitador de tasa, auditoría de acciones administrativas) en un módulo nuevo, con un endpoint mínimo de verificación de punta a punta. **No** se retro-adapta el control de alcances al resto de la API existente (reportes, inscripciones, certificados) — eso lo decidirá ENG-076 (Integraciones institucionales) caso por caso, endpoint por endpoint, según lo que cada integración real necesite.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Nuevo módulo `Modules\Integration`**, con un agregado `ApiConsumer` (identificación) que reutiliza el mismo patrón de llave que `Modules\Simulation` (hash SHA-256, revelado único) pero como una implementación propia, no compartida — los dos módulos son contextos delimitados independientes; duplicar una VO de ~20 líneas es más consistente con DDD que crear un kernel compartido para un caso de uso que solo se repite dos veces.
- **Alcances (scopes)**: `ApiConsumer` guarda una lista de strings validados contra `Modules\Authorization\Domain\Enums\Permission` (mismo vocabulario `recurso.accion`, dependencia cruzada de solo lectura hacia el enum, sin arrastrar `RolePermissions` ni el modelo de roles humanos).
- **Ciclo de vida**: `Active → Suspended → Active` (reactivar) o `Active/Suspended → Revoked` (terminal, sin retorno) — mismo patrón de historial append-only que `Simulator`. La revocación es inmediata: el middleware de autenticación rechaza cualquier consumidor que no esté `Active`.
- **Expiración**: campo opcional `expiresAt` verificado en el middleware de autenticación junto con el estado — una llave expirada se rechaza aunque su estado siga siendo `Active` (evita tener que recordar suspender manualmente una integración con fecha de fin conocida).
- **Endpoint mínimo de verificación**: `GET /api/v1/external/status` (requiere solo autenticación válida, sin alcance específico — "quién soy") y `GET /api/v1/external/reports/ping` (requiere el alcance `reports.view` — prueba de punta a punta de la verificación de alcances) — ambos triviales, no exponen ningún dato real de negocio.
- **Auditoría**: se auditan las acciones administrativas sobre un `ApiConsumer` (crear, suspender, reactivar, revocar, rotar llave) con `userId` igual al administrador que las ejecuta (un usuario real, compatible con la FK). **No** se audita cada petición autenticada de un consumidor externo (crear una entrada de auditoría por petición sería un volumen de eventos de acceso/observabilidad, no un evento de negocio — distinto de auditar quién cambió la configuración de una integración).

## Incluye (del roadmap)

- Identificación del consumidor (`ApiConsumer`, nombre + llave).
- Alcances (subconjunto del vocabulario de `Permission`).
- Revocación (terminal, inmediata).
- Expiración (campo opcional, verificado junto al estado).
- Rate limiting (limitador nombrado `external-integration`, mismo patrón de ENG-067).
- Auditoría (acciones administrativas sobre consumidores).

## Diferido explícitamente

- Retro-adaptación del control de alcances a la API de negocio existente (reportes, inscripciones, certificados, exportaciones) — se decide en ENG-076 según cada integración real.
- Auditoría de cada petición autenticada individual de un consumidor externo.
- Cualquier mecanismo de "shared kernel" entre `Modules\Simulation` y `Modules\Integration` para la llave de integración.
