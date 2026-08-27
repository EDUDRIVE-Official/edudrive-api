# ENG-057 — Preferencias de notificación: alcance acordado

Segunda historia de la Fase 11 — Comunicación y notificaciones. Extiende `Modules\Notification` con un segundo agregado, `NotificationPreference` — un registro de configuración por usuario (no un catálogo ni un ledger de solo-append), y modifica `SendNotificationHandler` (ENG-056) para consultarlo antes de registrar una notificación.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Aplicación activa**: `SendNotificationHandler` consulta las preferencias del destinatario antes de registrar la notificación. Si el canal, la categoría o el consentimiento no lo permiten, la notificación se descarta silenciosamente — no se crea ningún registro.
2. **Canales y categorías**: todo permitido por defecto, con silenciamiento explícito. `allowedChannels` es un subconjunto del enum cerrado `NotificationChannel` (por defecto, los cuatro); `mutedCategories` es una lista de texto libre que el usuario silencia explícitamente (por defecto, vacía). Un usuario nuevo recibe todo hasta que silencia algo puntual.
3. **Frecuencia y horarios**: solo configuración almacenada, sin aplicar. Se guardan `frequency` (`immediate`/`daily`/`weekly`) y un horario de silencio (`quietHoursStart`/`quietHoursEnd`, formato `HH:MM`), pero ninguno se aplica todavía al enviar — la agregación en lotes por frecuencia y el bloqueo real durante el horario de silencio requieren un motor de programación/cola que no existe aún.
4. **Consentimiento**: booleano simple `consentGiven`, otorgado por defecto (`true`) porque las notificaciones de esta plataforma son operativas/educativas, no de marketing. El usuario puede revocarlo explícitamente. Sin distinción por categoría ni versionado de políticas legales.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **`NotificationPreference` es un registro de configuración por usuario, no un catálogo ni un ledger**: una fila por usuario (clave primaria = `user_id`), actualizable en el tiempo — distinto de `Achievement`/`Badge` (catálogo+grant) y de `ExperienceEntry`/ `Notification` (solo-append).
- **Ausencia de preferencia = valores por defecto**: si un usuario no tiene un registro de preferencia, `GetMyNotificationPreferenceHandler`/`SendNotificationHandler` usan `NotificationPreference::default($userId)` (todo permitido, `immediate`, sin horario de silencio, consentimiento otorgado) en vez de exigir que cada usuario inicialice sus preferencias explícitamente.
- **`consentUpdatedAt` en vez de `consentGivenAt`**: registra la fecha del último cambio explícito de consentimiento (otorgar o revocar), no solo el otorgamiento — permanece `null` mientras el usuario nunca haya tocado el valor por defecto.
- **`SendNotificationHandler::handle()` retorna `?NotificationResponse`**: `null` cuando la notificación fue descartada por preferencia (no es un error del cliente, es un filtrado esperado). La API HTTP responde `200 OK` con `{"data": null}` en ese caso, en vez de `201 Created` con la notificación.
- **Gestión de preferencias 100% autoservicio**: sin permiso nuevo — un usuario solo puede leer/editar sus propias preferencias (`auth:sanctum`, sin gestión administrativa de las preferencias de otro usuario en esta historia).
- **Validación de horario en el dominio**: `NotificationPreference::update()` exige que `quietHoursStart`/`quietHoursEnd` sean ambos `null` o ambos un string `HH:MM` válido (regex) — sin restricción de orden, porque un horario de silencio puede cruzar la medianoche (ej. `22:00`–`07:00`).

## Incluye (del roadmap)

- Canales permitidos.
- Categorías.
- Frecuencia.
- Horarios.
- Consentimientos.

## Diferido explícitamente

- Aplicación real de la frecuencia (agregación en lotes/digest) — requiere un motor de programación/cola.
- Aplicación real del horario de silencio (bloqueo o diferimiento del envío durante esa ventana).
- Catálogo cerrado de categorías silenciables.
- Historial de consentimientos versionado por política legal.
- Gestión administrativa de las preferencias de otro usuario.
