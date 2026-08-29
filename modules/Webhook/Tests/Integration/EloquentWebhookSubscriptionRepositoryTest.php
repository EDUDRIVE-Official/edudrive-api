<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Enums\WebhookSubscriptionStatus;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Modules\Webhook\Infrastructure\Persistence\Eloquent\Models\WebhookSubscriptionModel;

uses(RefreshDatabase::class);

/** @param list<WebhookEventName> $events */
function newPersistableWebhookSubscription(array $events = [WebhookEventName::EnrollmentCreated]): WebhookSubscription
{
    return WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://example.test/webhooks',
        events: $events,
        secret: WebhookSigningSecret::generate(),
    );
}

it('guarda y recupera una suscripcion por identificador, cifrando el secreto en reposo', function (): void {
    $subscription = newPersistableWebhookSubscription();

    app(WebhookSubscriptionRepository::class)->save($subscription);
    $found = app(WebhookSubscriptionRepository::class)->findById($subscription->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($subscription->id()))->toBeTrue()
        ->and($found?->url())->toBe('https://example.test/webhooks')
        ->and($found?->events())->toBe([WebhookEventName::EnrollmentCreated])
        ->and($found?->status())->toBe(WebhookSubscriptionStatus::Active)
        ->and($found?->secret()->value())->toBe($subscription->secret()->value());

    $raw = WebhookSubscriptionModel::query()->where('id', $subscription->id()->value())->firstOrFail();
    expect($raw->getAttribute('secret_encrypted'))->not->toBe($subscription->secret()->value());
});

it('encuentra suscripciones activas por evento', function (): void {
    $repository = app(WebhookSubscriptionRepository::class);
    $matching = newPersistableWebhookSubscription([WebhookEventName::EnrollmentCreated, WebhookEventName::CertificateIssued]);
    $notMatching = newPersistableWebhookSubscription([WebhookEventName::CertificateIssued]);
    $suspended = newPersistableWebhookSubscription([WebhookEventName::EnrollmentCreated]);
    $suspended->suspend();

    $repository->save($matching);
    $repository->save($notMatching);
    $repository->save($suspended);

    $found = $repository->findActiveByEvent(WebhookEventName::EnrollmentCreated);

    expect($found)->toHaveCount(1)
        ->and($found[0]->id()->equals($matching->id()))->toBeTrue();
});

it('lista todas las suscripciones registradas', function (): void {
    $repository = app(WebhookSubscriptionRepository::class);
    $repository->save(newPersistableWebhookSubscription());
    $repository->save(newPersistableWebhookSubscription());

    expect($repository->all())->toHaveCount(2);
});
