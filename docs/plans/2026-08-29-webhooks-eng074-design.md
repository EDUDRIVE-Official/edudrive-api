# ENG-074 — Webhooks: alcance acordado

Segunda historia de la Fase 15 — Integraciones. El roadmap pide seis puntos: Eventos, Firmas, Reintentos, Idempotencia, Registro de entregas, Dead-letter handling.

## Estado previo encontrado (investigación, no una decisión del usuario)

- No existe ningún mecanismo de eventos de dominio en todo el repositorio: cero uso de `Illuminate\Events\Dispatcher`/`event()`, cero directorios `Modules\*\Domain\Events`, cero concepto de "suscripción a webhook". Los efectos secundarios de negocio (ej. una inscripción creada, un certificado emitido) hoy son llamadas síncronas directas dentro de los propios handlers vía el `CommandBus`, no eventos.
- No existe ningún cliente HTTP saliente en el repositorio (cero uso de `Http::`/Guzzle). `Modules\Notification` solo persiste registros de notificación en base de datos (`Notification` aggregate: canal/asunto/cuerpo/estado leído-no-leído); no modela intentos de entrega, reintentos ni dead-lettering.
- La cola de Laravel está configurada (`config/queue.php`, driver `database`, tablas `jobs`/`failed_jobs`/`job_batches` migradas) pero **nunca se ha usado realmente**: cero clases `Job`, cero `ShouldQueue` en todo el repositorio. Esta historia es el primer uso real de la cola.
- El patrón más parecido reutilizable es `Modules\Integration`'s `ApiConsumer`/`IntegrationKey` (ENG-073): ciclo de vida administrado + secreto + auditoría de acciones administrativas. **Diferencia importante que invalida reutilizar `IntegrationKey` tal cual**: `IntegrationKey` solo guarda el hash SHA-256 del secreto (autenticación por comparación de hash, nunca necesita el valor en texto plano de vuelta). Un secreto de firma de webhook necesita lo opuesto: debe poder **recuperarse** en cada entrega para calcular el HMAC saliente. Por eso el secreto de webhook se cifra reversiblemente (Laravel `Crypt`), no se hashea de forma irreversible.

## Decisiones confirmadas con el usuario (AskUserQuestion)

1. **Alcance reducido**: se construye el mecanismo completo de webhooks (suscripciones administradas, firma HMAC-SHA256, entrega asíncrona vía Job real de Laravel, reintentos con backoff exponencial, registro de entregas, dead-letter tras N intentos) pero solo se cablean **dos eventos de dominio reales** como emisores de prueba (`enrollment.created`, `certificate.issued`), en vez de retrofitear un bus de eventos genérico a través de todos los módulos. Extender a más eventos de negocio se decide cuando haya una necesidad concreta.

## Decisiones de diseño (juicio propio, siguiendo precedentes de la sesión)

- **Nuevo módulo `Modules\Webhook`** con dos conceptos de dominio separados:
  - `WebhookSubscription` (agregado): url, `WebhookSigningSecret`, lista de nombres de evento suscritos (validados contra un enum cerrado `WebhookEventName` con exactamente los dos valores del alcance reducido — mismo patrón de validación que `InvalidApiConsumerScope` en ENG-073, aquí `InvalidWebhookEventName`), estado `Active|Suspended` (sin estado terminal ni borrado — una suscripción que ya no se necesita simplemente se suspende; se evita la complejidad de decidir qué pasa con el historial de entregas de una suscripción borrada).
  - `WebhookDelivery` (entidad, una fila por combinación evento-suscripción): nombre del evento, payload, estado `Pending|Delivered|Failed|DeadLettered`, contador de intentos, fecha del último intento, código/cuerpo de la última respuesta (truncado), próxima fecha de reintento. **Una fila por entrega, no una fila por intento** — el contador de intentos y el último resultado son suficientes para "registro de entregas" sin modelar cada intento individual como una entidad separada.
- **`WebhookSigningSecret`**: la Capa de Dominio solo conoce el valor en texto plano (necesario para firmar) — el cifrado en reposo (`Crypt::encryptString()`/`decryptString()`) es una responsabilidad exclusiva de `EloquentWebhookSubscriptionRepository` (Infraestructura), igual que el resto del repositorio ya depende de Eloquent/Laravel. El valor en texto plano nunca se expone vía API salvo justo al crear o rotar la suscripción (mismo patrón que `integrationKey` en `ApiConsumerResponse`/`SimulatorResponse`).
- **Firma**: `X-Webhook-Signature: sha256=<hmac hexadecimal>` calculado con `hash_hmac('sha256', $payloadJson, $secretoEnTextoPlano)` sobre el cuerpo JSON exacto que se envía — sin componente de timestamp ni ventana anti-replay (mismo convenio que el header `X-Hub-Signature-256` de GitHub). Se documenta como limitación deliberada, no como omisión accidental.
- **Payload "delgado"**: cada evento envía únicamente identificadores (`{event, occurred_at, data: {...ids...}}`), no el recurso completo — convención deliberada de varios sistemas de webhooks en producción (p. ej. "thin events" de Stripe) para que el receptor vuelva a consultar la API por el estado actual en vez de depender de una copia potencialmente desactualizada del payload. `enrollment.created` envía `{enrollment_id, user_id, course_id, organization_id}`; `certificate.issued` envía `{certificate_id, user_id, course_id}`.
- **Publicación de eventos sin bus genérico**: `Modules\Webhook\Application\Services\WebhookEventPublisher` (puerto) con un único método `publish(WebhookEvent $event): void`. `Modules\Academic`'s `CreateEnrollmentHandler` y `Modules\Certification`'s `IssueCertificateHandler` reciben esta interfaz como dependencia nueva y la llaman tras guardar el agregado — la misma convención ya usada en la sesión de una Capa de Aplicación dependiendo de una interfaz de otro módulo (p. ej. `Modules\Legal` dependiendo del `UserRepository` de `Modules\Identity`). La implementación (`QueuedWebhookEventPublisher`, Infraestructura) busca las suscripciones activas para ese nombre de evento, crea una fila `WebhookDelivery` por cada una y despacha `DeliverWebhookJob` a la cola real de Laravel.
- **Entrega, reintentos y dead-letter viven en el propio dominio, no en la maquinaria nativa de reintento de colas de Laravel**: `DeliverWebhookJob` se ejecuta una vez por invocación (`$tries = 1`); toda la lógica de conteo de intentos, backoff y dead-letter está en `WebhookDelivery` y se re-despacha manualmente (`DeliverWebhookJob::dispatch($deliveryId)->delay($segundos)`). Esto mantiene el estado de reintento visible en el modelo de dominio propio (consultable vía API) en vez de escondido en la tabla `failed_jobs` de Laravel. Backoff exponencial con techo: `30s * 2^(intentos-1)`, máximo 1 hora; tras 5 intentos fallidos, `DeadLettered`.
- **Idempotencia orientada al receptor**: cada `WebhookDelivery` tiene un id estable (UUID) enviado en cada intento (incluidos los reintentos) como header `X-Webhook-Delivery-Id`, para que el receptor pueda deduplicar reintentos de la misma entrega — convención estándar de idempotencia de webhooks (GitHub, Stripe), no una tabla de deduplicación propia adicional.
- **Recuperación manual de entregas muertas**: `POST /api/v1/webhooks/subscriptions/{id}/deliveries/{deliveryId}/retry` (solo permitido si el estado es `Failed`/`DeadLettered`) reencola una entrega inmediatamente, sin esperar el backoff — sin esto, una entrega en `DeadLettered` quedaría inerte para siempre.
- **Auditoría**: se auditan las acciones administrativas sobre una suscripción (crear, suspender, reactivar, rotar secreto) con `userId` del administrador que las ejecuta. No se audita cada intento de entrega individual (sería volumen de observabilidad, no un evento de negocio) — para eso está el propio registro de `WebhookDelivery`, consultable vía API.
- **Permisos nuevos**: `webhooks.manage` / `webhooks.view`, otorgados únicamente a `SuperAdmin` (mismo patrón que `api_consumers.manage`/`.view`).

## Incluye (del roadmap)

- Eventos (`WebhookEventName` cerrado a dos valores; `WebhookEventPublisher` cableado en `CreateEnrollmentHandler` e `IssueCertificateHandler`).
- Firmas (HMAC-SHA256 sobre el payload, header `X-Webhook-Signature`).
- Reintentos (backoff exponencial con techo, hasta 5 intentos, vía cola real de Laravel).
- Idempotencia (header `X-Webhook-Delivery-Id` estable entre reintentos de la misma entrega).
- Registro de entregas (`WebhookDelivery`: estado, intentos, última respuesta, consultable vía API).
- Dead-letter handling (estado terminal `DeadLettered` tras 5 intentos + endpoint de recuperación manual).

## Diferido explícitamente

- Un bus de eventos de dominio genérico retrofiteado a través de todos los módulos — solo se cablean los dos eventos del alcance reducido.
- Ventana anti-replay basada en timestamp en la firma HMAC.
- Borrado de suscripciones (solo suspensión/reactivación).
- Auditoría de cada intento de entrega individual.
- Cualquier reutilización directa de `IntegrationKey` (`Modules\Simulation`/`Modules\Integration`) — el secreto de webhook necesita cifrado reversible, no hash irreversible.
