<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Enums\WebhookSubscriptionStatus;
use Modules\Webhook\Domain\Exceptions\InvalidWebhookSubscriptionTransition;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

/** @param list<WebhookEventName> $events */
function newWebhookSubscription(array $events = [WebhookEventName::EnrollmentCreated]): WebhookSubscription
{
    return WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://example.test/webhooks',
        events: $events,
        secret: WebhookSigningSecret::generate(),
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra en estado active', function (): void {
    $subscription = newWebhookSubscription();

    expect($subscription->status())->toBe(WebhookSubscriptionStatus::Active)
        ->and($subscription->isActive())->toBeTrue()
        ->and($subscription->url())->toBe('https://example.test/webhooks');
});

it('verifica si esta suscrita a un evento concreto', function (): void {
    $subscription = newWebhookSubscription([WebhookEventName::EnrollmentCreated]);

    expect($subscription->subscribesTo(WebhookEventName::EnrollmentCreated))->toBeTrue()
        ->and($subscription->subscribesTo(WebhookEventName::CertificateIssued))->toBeFalse();
});

it('suspende una suscripcion activa', function (): void {
    $subscription = newWebhookSubscription();

    $subscription->suspend();

    expect($subscription->status())->toBe(WebhookSubscriptionStatus::Suspended)
        ->and($subscription->isActive())->toBeFalse();
});

it('rechaza suspender una suscripcion ya suspendida', function (): void {
    $subscription = newWebhookSubscription();
    $subscription->suspend();

    expect(fn () => $subscription->suspend())->toThrow(InvalidWebhookSubscriptionTransition::class);
});

it('reactiva una suscripcion suspendida', function (): void {
    $subscription = newWebhookSubscription();
    $subscription->suspend();

    $subscription->reactivate();

    expect($subscription->status())->toBe(WebhookSubscriptionStatus::Active);
});

it('rechaza reactivar una suscripcion activa', function (): void {
    $subscription = newWebhookSubscription();

    expect(fn () => $subscription->reactivate())->toThrow(InvalidWebhookSubscriptionTransition::class);
});

it('rota el secreto de firma', function (): void {
    $subscription = newWebhookSubscription();
    $originalValue = $subscription->secret()->value();
    $newSecret = WebhookSigningSecret::generate();

    $subscription->rotateSecret($newSecret);

    expect($subscription->secret()->value())->toBe($newSecret->value())
        ->and($subscription->secret()->value())->not->toBe($originalValue);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = WebhookSubscriptionId::fromString((string) Str::uuid());
    $secret = WebhookSigningSecret::fromPlainValue('secreto-de-prueba');
    $createdAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');

    $subscription = WebhookSubscription::restore(
        id: $id,
        url: 'https://example.test/webhooks',
        events: [WebhookEventName::EnrollmentCreated, WebhookEventName::CertificateIssued],
        status: WebhookSubscriptionStatus::Suspended,
        secret: $secret,
        createdAt: $createdAt,
    );

    expect($subscription->id()->equals($id))->toBeTrue()
        ->and($subscription->url())->toBe('https://example.test/webhooks')
        ->and($subscription->events())->toBe([WebhookEventName::EnrollmentCreated, WebhookEventName::CertificateIssued])
        ->and($subscription->status())->toBe(WebhookSubscriptionStatus::Suspended)
        ->and($subscription->secret()->value())->toBe('secreto-de-prueba')
        ->and($subscription->createdAt())->toBe($createdAt);
});
