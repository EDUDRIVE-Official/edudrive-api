# ENG-075 — Integración con aplicaciones móviles: alcance acordado

Tercera historia de la Fase 15 — Integraciones. El roadmap pide cinco puntos: Versionado, Compatibilidad, Sincronización, Tokens por dispositivo, Notificaciones móviles.

## Estado previo encontrado (investigación, no una decisión del usuario)

- **Versionado**: no existe nada más allá del prefijo fijo `api/v1/` repetido literalmente en cada módulo. Sin negociación por header, sin mecanismo de deprecación.
- **Tokens por dispositivo**: Sanctum's `createToken($name, $abilities)` acepta un `$name` de texto libre (`LoginController` lo alimenta desde `token_name` del request) pero no existe ningún concepto estructurado de "dispositivo" (sin `device_id`, plataforma, push token). `Modules\Identity` ya tiene `GET /api/v1/auth/sessions` (lista tokens Sanctum del usuario) y `POST /api/v1/auth/logout-all` (revoca todos) — una funcionalidad real de "mis sesiones activas" ya existe y no se toca en esta historia.
- **Notificaciones móviles**: `Modules\Notification`'s `Notification` aggregate ya tiene un canal `Mobile` en su enum (`NotificationChannel::Mobile`), pero `SendNotificationHandler` únicamente persiste el registro en base de datos — cero transporte de salida para cualquier canal, cero cliente HTTP, cero integración FCM/APNs en todo el repositorio.
- **Sincronización**: solo existe para telemetría de simuladores (ENG-050, subida idempotente vía `insertOrIgnore`) — nada equivalente para que una app móvil descargue cambios incrementales de sus propios datos (inscripciones, notificaciones, etc.).
- **Compatibilidad**: no existe un concepto de "versión mínima soportada" ni "forzar actualización", pero `Modules\Admin`'s `SystemSetting` (par clave/valor genérico, ya usado para configuración en producción desde ENG-068/069) sirve tal cual para guardar `mobile_min_app_version` sin ningún cambio de esquema.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: módulo nuevo `Modules\Mobile` con registro de dispositivo (identificación + push token + versión de app), un middleware de compatibilidad que lee `SystemSetting` existente, un envío real de notificaciones push vía HTTP a un endpoint configurable (mismo patrón de cola real que `DeliverWebhookJob`, ENG-074), y un único endpoint ilustrativo de sincronización incremental — sin retrofitear `updated_since`/cursor a través de todos los módulos existentes ni integrar SDKs reales de FCM/APNs con certificados.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Nuevo módulo `Modules\Mobile`** con `MobileDevice` (agregado): `deviceId` (identificador que el propio cliente genera y reporta, no el id interno), `userId`, `platform` (`DevicePlatform::iOS|Android`), `pushToken` (opcional — un dispositivo puede registrarse sin push token si el usuario no otorgó el permiso de notificaciones), `appVersion`, `lastSeenAt`. Único por `(userId, deviceId)` a nivel de esquema.
- **"Tokens por dispositivo" se interpreta como gestión de push tokens, no como extender Sanctum**: `Modules\Identity` ya resuelve "sesiones activas por dispositivo" (`GET /auth/sessions`, `POST /auth/logout-all`) — no se le agrega ningún concepto de dispositivo a Sanctum. `MobileDevice` es un registro independiente, propósito único: saber a qué push token(s) enviarle una notificación móvil. Registrar de nuevo el mismo `(userId, deviceId)` **actualiza** el registro existente (nuevo push token, nueva versión de app, `lastSeenAt` refrescado) — a diferencia del patrón de idempotencia de ENG-072 (que devuelve el recurso sin cambios), aquí sí se actualiza porque los metadatos de un dispositivo legítimamente cambian con el tiempo.
- **Compatibilidad**: middleware `EnsureMinimumAppVersion` (alias `mobile.min_version`) lee la configuración `mobile_min_app_version` vía `Modules\Admin`'s `SystemSettingRepository` directamente (dependencia de solo lectura entre módulos, mismo patrón ya usado en la sesión) y compara contra el header `X-App-Version` con `version_compare()`. Sin configuración registrada, no bloquea (comportamiento por defecto permisivo). Con configuración pero sin header, responde 400 (`MISSING_APP_VERSION`). Por debajo del mínimo, responde 426 Upgrade Required (`APP_VERSION_UNSUPPORTED`). No se crea ningún endpoint administrativo nuevo — la configuración ya es gestionable vía el endpoint existente de `Modules\Admin` (`system_settings.manage`).
- **Notificaciones móviles**: `Modules\Mobile\Application\Services\MobilePushSender` (puerto) consumido por `Modules\Notification`'s `SendNotificationHandler` — cuando el canal es `Mobile`, tras guardar la notificación se llama al puerto con el `userId`/asunto/cuerpo. La implementación (`QueuedMobilePushSender`) busca los dispositivos del usuario con push token registrado y despacha un `SendMobilePushJob` (cola real) por cada uno, que hace un POST HTTP a un endpoint configurable (`config('mobile.push_endpoint')`, compatible con la forma de la API HTTP legacy de FCM) con el token como destinatario. **Deliberadamente sin el aparato de registro de entregas/reintento con backoff/dead-letter de ENG-074**: un push best-effort no amerita esa durabilidad — si falla, se apoya en el reintento nativo por defecto de Laravel para jobs, sin modelo de dominio propio de reintento.
- **Sincronización**: un único endpoint `GET /api/v1/mobile/sync?since=<ISO8601>` que devuelve las inscripciones y notificaciones del usuario autenticado creadas después de `since`, filtrando en memoria sobre lo que ya devuelven `EnrollmentRepository::all(userId:)` y `NotificationRepository::allForUser()` — sin agregar parámetros nuevos a esas interfaces ni tocar ningún otro módulo. Es una simplificación deliberada: no rastrea *cambios* de estado (p. ej. una inscripción que pasó de activa a completada no aparece si ya existía antes de `since`), solo *creación* — suficiente para demostrar el mecanismo sin comprometerse a un sistema de sincronización completo por ahora.

## Incluye (del roadmap)

- Versionado y Compatibilidad (`mobile_min_app_version` vía `SystemSetting` + middleware `EnsureMinimumAppVersion`).
- Sincronización (endpoint ilustrativo `GET /api/v1/mobile/sync`).
- Tokens por dispositivo (`MobileDevice`: registro, actualización, listado, baja individual).
- Notificaciones móviles (`MobilePushSender` + `SendMobilePushJob`, cableado en `SendNotificationHandler` para el canal `Mobile`).

## Diferido explícitamente

- Sincronización incremental real (`updated_since`/cursor) retrofiteada a través de cursos, progreso, gamificación, pasaporte vial, etc.
- Integración real con SDKs de FCM/APNs con autenticación por certificado — el endpoint HTTP es genérico y configurable, no una integración certificada.
- Cualquier concepto de "dispositivo" en Sanctum/`Modules\Identity` — la gestión de sesiones activas ya existente no se modifica.
- Registro de entregas, reintento con backoff y dead-letter para notificaciones push (sí existe para webhooks, ENG-074) — un push best-effort no lo amerita.
