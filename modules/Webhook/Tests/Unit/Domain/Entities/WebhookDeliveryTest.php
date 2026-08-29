<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Exceptions\InvalidWebhookDeliveryRetry;

function newWebhookDelivery(): WebhookDelivery
{
    return WebhookDelivery::create(
        id: (string) Str::uuid(),
        subscriptionId: (string) Str::uuid(),
        eventName: WebhookEventName::EnrollmentCreated,
        payload: ['enrollment_id' => 'e-1'],
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se crea en estado pending sin intentos', function (): void {
    $delivery = newWebhookDelivery();

    expect($delivery->status())->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->attempts())->toBe(0)
        ->and($delivery->lastAttemptedAt())->toBeNull()
        ->and($delivery->nextRetryAt())->toBeNull();
});

it('se marca como entregada con la respuesta recibida', function (): void {
    $delivery = newWebhookDelivery();

    $delivery->markDelivered(200, 'ok', new DateTimeImmutable('2026-08-29T10:00:05+00:00'));

    expect($delivery->status())->toBe(WebhookDeliveryStatus::Delivered)
        ->and($delivery->lastResponseStatus())->toBe(200)
        ->and($delivery->lastResponseBody())->toBe('ok')
        ->and($delivery->nextRetryAt())->toBeNull();
});

it('registra un intento fallido y calcula la proxima fecha de reintento con backoff exponencial', function (): void {
    $delivery = newWebhookDelivery();
    $at = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    $delivery->recordFailedAttempt(500, 'error', $at);

    expect($delivery->status())->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempts())->toBe(1)
        ->and($delivery->lastResponseStatus())->toBe(500)
        ->and($delivery->nextRetryAt())->toEqual($at->modify('+30 seconds'));
});

it('aumenta el backoff exponencialmente en cada intento fallido sucesivo', function (): void {
    $delivery = newWebhookDelivery();
    $at = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    $delivery->recordFailedAttempt(500, null, $at);
    expect($delivery->nextRetryAt())->toEqual($at->modify('+30 seconds'));

    $delivery->recordFailedAttempt(500, null, $at);
    expect($delivery->nextRetryAt())->toEqual($at->modify('+60 seconds'));

    $delivery->recordFailedAttempt(500, null, $at);
    expect($delivery->nextRetryAt())->toEqual($at->modify('+120 seconds'));
});

it('pasa a dead-lettered tras el quinto intento fallido', function (): void {
    $delivery = newWebhookDelivery();
    $at = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    for ($i = 1; $i <= 4; $i++) {
        $delivery->recordFailedAttempt(500, null, $at);
        expect($delivery->status())->toBe(WebhookDeliveryStatus::Failed);
    }

    $delivery->recordFailedAttempt(500, 'error final', $at);

    expect($delivery->status())->toBe(WebhookDeliveryStatus::DeadLettered)
        ->and($delivery->attempts())->toBe(5)
        ->and($delivery->nextRetryAt())->toBeNull();
});

it('permite reintentar manualmente una entrega fallida o en dead-letter', function (): void {
    $delivery = newWebhookDelivery();
    $delivery->recordFailedAttempt(500, null, new DateTimeImmutable('now'));

    $delivery->retryNow();

    expect($delivery->status())->toBe(WebhookDeliveryStatus::Pending)
        ->and($delivery->nextRetryAt())->toBeNull()
        ->and($delivery->attempts())->toBe(1);
});

it('rechaza reintentar manualmente una entrega pendiente o entregada', function (): void {
    $pending = newWebhookDelivery();
    expect(fn () => $pending->retryNow())->toThrow(InvalidWebhookDeliveryRetry::class);

    $delivered = newWebhookDelivery();
    $delivered->markDelivered(200, 'ok', new DateTimeImmutable('now'));
    expect(fn () => $delivered->retryNow())->toThrow(InvalidWebhookDeliveryRetry::class);
});

it('restaura la entidad completa desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $subscriptionId = (string) Str::uuid();
    $createdAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $lastAttemptedAt = new DateTimeImmutable('2026-08-29T10:05:00+00:00');
    $nextRetryAt = new DateTimeImmutable('2026-08-29T10:05:30+00:00');

    $delivery = WebhookDelivery::restore(
        id: $id,
        subscriptionId: $subscriptionId,
        eventName: WebhookEventName::CertificateIssued,
        payload: ['certificate_id' => 'c-1'],
        status: WebhookDeliveryStatus::Failed,
        attempts: 1,
        lastAttemptedAt: $lastAttemptedAt,
        lastResponseStatus: 500,
        lastResponseBody: 'error',
        nextRetryAt: $nextRetryAt,
        createdAt: $createdAt,
    );

    expect($delivery->id())->toBe($id)
        ->and($delivery->subscriptionId())->toBe($subscriptionId)
        ->and($delivery->eventName())->toBe(WebhookEventName::CertificateIssued)
        ->and($delivery->payload())->toBe(['certificate_id' => 'c-1'])
        ->and($delivery->status())->toBe(WebhookDeliveryStatus::Failed)
        ->and($delivery->attempts())->toBe(1)
        ->and($delivery->lastAttemptedAt())->toBe($lastAttemptedAt)
        ->and($delivery->lastResponseStatus())->toBe(500)
        ->and($delivery->lastResponseBody())->toBe('error')
        ->and($delivery->nextRetryAt())->toBe($nextRetryAt)
        ->and($delivery->createdAt())->toBe($createdAt);
});
