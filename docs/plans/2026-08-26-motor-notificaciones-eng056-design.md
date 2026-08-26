# ENG-056 — Motor de notificaciones: alcance acordado

Primera historia de la Fase 11 — Comunicación y notificaciones. Nuevo módulo `Modules\Notification`.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Entrega**: solo registro y seguimiento — la notificación se persiste con su canal como metadato (`email`/`web`/`mobile`/`internal_message`), sin integrar realmente con proveedores SMTP o de push. La entrega real por cada canal externo queda diferida como preocupación de infraestructura, mismo criterio que el catálogo real de vehículos/escenarios diferido en ENG-046.
2. **Envío**: manual vía `notifications.manage` (SuperAdmin/InstitutionalAdmin), mismo criterio que `Achievement`/`Badge`/`Challenge`. Sin disparo automático desde otros módulos en esta historia (ej. no se integra todavía con el otorgamiento de un logro).
3. **Seguimiento**: estado de lectura simple `unread`/`read`. El propio destinatario marca sus notificaciones como leídas, en autoservicio — sin permiso especial, ya que solo afecta a sus propias notificaciones, con verificación de pertenencia (patrón anti-fuga: `NotificationNotFound` tanto si no existe como si no pertenece al solicitante, mismo criterio usado en `RoadPassport`/`SimulationSession`).
4. **Categoría**: campo de texto libre `category` (ej. "logro", "certificado"), sin catálogo cerrado todavía — ENG-057 (Preferencias de notificación) decidirá el catálogo de categorías y cómo se filtran.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **`Notification` no es un catálogo con grant separado**: a diferencia de `Achievement`/`Badge` (catálogo + grant), aquí es una sola entidad por notificación individual — no existe un concepto de "plantilla" en esta historia (eso es ENG-058, Plantillas de comunicación).
- **`markAsRead()` como transición de estado propia**: mismo patrón que `ChallengeParticipation::complete()` — un mutador con guarda de invariante (`InvalidNotificationTransition`, 422, si ya está `read`).
- **Un solo permiso nuevo, `notifications.manage`** (SuperAdmin + InstitutionalAdmin). Sin `notifications.view`, porque no hay un catálogo administrable que listar — la consulta es autoservicio únicamente, mismo criterio que `experience.manage` (sin `experience.view`).
- **Bootstrap de módulo nuevo**: al ser la primera historia de un módulo nuevo, incluye el mismo paso de arranque que ENG-051 (`GamificationServiceProvider` en su momento): `NotificationServiceProvider`, endpoint `/status`, y registro en `bootstrap/providers.php`.

## Canales previstos (del roadmap)

- Correo electrónico.
- Notificación web.
- Notificación móvil.
- Mensajes internos.

## Diferido explícitamente

- Integración real de entrega por cada canal (SMTP, proveedor push).
- Disparo automático de notificaciones desde eventos de otros módulos.
- Estado de entrega granular (pending/sent/delivered/failed) y reintentos.
- Catálogo cerrado de categorías (corresponde a ENG-057).
- Plantillas de comunicación (corresponde a ENG-058).
