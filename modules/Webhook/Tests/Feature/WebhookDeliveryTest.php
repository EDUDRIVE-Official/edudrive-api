<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Webhook\Application\Services\WebhookEventPublisher;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookEvent;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @return array{0: WebhookSubscription, 1: WebhookSigningSecret} */
function persistedActiveWebhookSubscriptionForDelivery(string $url = 'https://example.test/receiver'): array
{
    $secret = WebhookSigningSecret::generate();
    $subscription = WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: $url,
        events: [WebhookEventName::EnrollmentCreated],
        secret: $secret,
    );
    app(WebhookSubscriptionRepository::class)->save($subscription);

    return [$subscription, $secret];
}

it('entrega un evento con firma HMAC valida y lo marca como entregado', function (): void {
    /** @var TestCase $this */
    [$subscription, $secret] = persistedActiveWebhookSubscriptionForDelivery();
    Http::fake(['example.test/*' => Http::response('ok', 200)]);

    app(WebhookEventPublisher::class)->publish(new WebhookEvent(
        name: WebhookEventName::EnrollmentCreated,
        payload: ['enrollment_id' => 'e-1'],
        occurredAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    ));

    Http::assertSent(function (HttpClientRequest $request) use ($subscription, $secret): bool {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->body(), $secret->value());

        return $request->url() === $subscription->url()
            && $request->hasHeader('X-Webhook-Signature', $expectedSignature)
            && $request->hasHeader('X-Webhook-Event', 'enrollment.created')
            && $request->hasHeader('X-Webhook-Delivery-Id');
    });

    $deliveries = app(WebhookDeliveryRepository::class)->findBySubscription($subscription->id());
    expect($deliveries)->toHaveCount(1)
        ->and($deliveries[0]->status())->toBe(WebhookDeliveryStatus::Delivered)
        ->and($deliveries[0]->attempts())->toBe(0);
});

it('no entrega a suscripciones que no estan suscritas al evento', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    Http::fake();

    app(WebhookEventPublisher::class)->publish(new WebhookEvent(
        name: WebhookEventName::CertificateIssued,
        payload: ['certificate_id' => 'c-1'],
        occurredAt: new DateTimeImmutable('now'),
    ));

    Http::assertNothingSent();
    expect(app(WebhookDeliveryRepository::class)->findBySubscription($subscription->id()))->toBeEmpty();
});

it('reintenta con backoff y termina en dead-letter tras agotar los intentos', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    Http::fake(['example.test/*' => Http::response('error interno', 500)]);

    app(WebhookEventPublisher::class)->publish(new WebhookEvent(
        name: WebhookEventName::EnrollmentCreated,
        payload: ['enrollment_id' => 'e-1'],
        occurredAt: new DateTimeImmutable('now'),
    ));

    Http::assertSentCount(5);

    $deliveries = app(WebhookDeliveryRepository::class)->findBySubscription($subscription->id());
    expect($deliveries)->toHaveCount(1)
        ->and($deliveries[0]->status())->toBe(WebhookDeliveryStatus::DeadLettered)
        ->and($deliveries[0]->attempts())->toBe(5)
        ->and($deliveries[0]->lastResponseStatus())->toBe(500);
});

it('salta la entrega y registra un intento fallido cuando la suscripcion no esta activa', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    $subscription->suspend();
    app(WebhookSubscriptionRepository::class)->save($subscription);
    Http::fake();

    app(WebhookEventPublisher::class)->publish(new WebhookEvent(
        name: WebhookEventName::EnrollmentCreated,
        payload: ['enrollment_id' => 'e-1'],
        occurredAt: new DateTimeImmutable('now'),
    ));

    Http::assertNothingSent();
});

it('permite reintentar manualmente una entrega en dead-letter vía el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    $delivery = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, ['enrollment_id' => 'e-1']);
    for ($i = 0; $i < 5; $i++) {
        $delivery->recordFailedAttempt(500, null, new DateTimeImmutable('now'));
    }
    app(WebhookDeliveryRepository::class)->save($delivery);
    expect($delivery->status())->toBe(WebhookDeliveryStatus::DeadLettered);

    Http::fake(['example.test/*' => Http::response('ok', 200)]);
    actingAsRole(Role::SuperAdmin);

    $this->postJson("/api/v1/webhooks/deliveries/{$delivery->id()}/retry")
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');

    Http::assertSentCount(1);
    expect(app(WebhookDeliveryRepository::class)->findById($delivery->id())?->status())->toBe(WebhookDeliveryStatus::Delivered);
});

it('lista las entregas de una suscripcion filtradas por estado vía el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    $delivered = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $delivered->markDelivered(200, 'ok', new DateTimeImmutable('now'));
    app(WebhookDeliveryRepository::class)->save($delivered);

    $failed = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $failed->recordFailedAttempt(500, null, new DateTimeImmutable('now'));
    app(WebhookDeliveryRepository::class)->save($failed);

    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}/deliveries")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}/deliveries?status=failed")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $failed->id());
});

it('rechaza un filtro de estado invalido al listar entregas', function (): void {
    /** @var TestCase $this */
    [$subscription] = persistedActiveWebhookSubscriptionForDelivery();
    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/webhooks/subscriptions/{$subscription->id()->value()}/deliveries?status=no-es-un-estado")
        ->assertStatus(422);
});
