<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

uses(RefreshDatabase::class);

function persistedWebhookSubscriptionForDelivery(): WebhookSubscription
{
    $subscription = WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://example.test/webhooks',
        events: [WebhookEventName::EnrollmentCreated],
        secret: WebhookSigningSecret::generate(),
    );
    app(WebhookSubscriptionRepository::class)->save($subscription);

    return $subscription;
}

it('guarda y recupera una entrega por identificador', function (): void {
    $subscription = persistedWebhookSubscriptionForDelivery();
    $delivery = WebhookDelivery::create(
        id: (string) Str::uuid(),
        subscriptionId: $subscription->id()->value(),
        eventName: WebhookEventName::EnrollmentCreated,
        payload: ['enrollment_id' => 'e-1', 'user_id' => 'u-1'],
    );

    app(WebhookDeliveryRepository::class)->save($delivery);
    $found = app(WebhookDeliveryRepository::class)->findById($delivery->id());

    expect($found)->not->toBeNull()
        ->and($found?->id())->toBe($delivery->id())
        ->and($found?->subscriptionId())->toBe($subscription->id()->value())
        ->and($found?->eventName())->toBe(WebhookEventName::EnrollmentCreated)
        ->and($found?->payload())->toBe(['enrollment_id' => 'e-1', 'user_id' => 'u-1'])
        ->and($found?->status())->toBe(WebhookDeliveryStatus::Pending)
        ->and($found?->attempts())->toBe(0);
});

it('guarda y recupera el estado de un intento fallido', function (): void {
    $subscription = persistedWebhookSubscriptionForDelivery();
    $delivery = WebhookDelivery::create(
        id: (string) Str::uuid(),
        subscriptionId: $subscription->id()->value(),
        eventName: WebhookEventName::EnrollmentCreated,
        payload: [],
    );
    $delivery->recordFailedAttempt(500, 'error interno', new DateTimeImmutable('2026-08-29T10:00:00+00:00'));

    app(WebhookDeliveryRepository::class)->save($delivery);
    $found = app(WebhookDeliveryRepository::class)->findById($delivery->id());

    expect($found?->status())->toBe(WebhookDeliveryStatus::Failed)
        ->and($found?->attempts())->toBe(1)
        ->and($found?->lastResponseStatus())->toBe(500)
        ->and($found?->lastResponseBody())->toBe('error interno')
        ->and($found?->nextRetryAt())->not->toBeNull();
});

it('lista las entregas de una suscripcion, opcionalmente filtradas por estado', function (): void {
    $subscription = persistedWebhookSubscriptionForDelivery();
    $repository = app(WebhookDeliveryRepository::class);

    $delivered = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $delivered->markDelivered(200, 'ok', new DateTimeImmutable('now'));
    $repository->save($delivered);

    $failed = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $failed->recordFailedAttempt(500, null, new DateTimeImmutable('now'));
    $repository->save($failed);

    expect($repository->findBySubscription($subscription->id()))->toHaveCount(2)
        ->and($repository->findBySubscription($subscription->id(), WebhookDeliveryStatus::Failed))->toHaveCount(1)
        ->and($repository->findBySubscription($subscription->id(), WebhookDeliveryStatus::Delivered))->toHaveCount(1);
});
