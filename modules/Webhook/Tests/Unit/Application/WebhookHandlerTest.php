<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Webhook\Application\Commands\ReactivateWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RegisterWebhookSubscriptionCommand;
use Modules\Webhook\Application\Commands\RetryWebhookDeliveryCommand;
use Modules\Webhook\Application\Commands\RotateWebhookSubscriptionSecretCommand;
use Modules\Webhook\Application\Commands\SuspendWebhookSubscriptionCommand;
use Modules\Webhook\Application\Exceptions\InvalidWebhookEventName;
use Modules\Webhook\Application\Exceptions\WebhookDeliveryNotFound;
use Modules\Webhook\Application\Exceptions\WebhookSubscriptionNotFound;
use Modules\Webhook\Application\Queries\GetWebhookSubscriptionQuery;
use Modules\Webhook\Application\Queries\ListWebhookDeliveriesQuery;
use Modules\Webhook\Application\Queries\ListWebhookSubscriptionsQuery;
use Modules\Webhook\Application\Responses\WebhookDeliveryResponse;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Application\UseCases\GetWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\ListWebhookDeliveriesHandler;
use Modules\Webhook\Application\UseCases\ListWebhookSubscriptionsHandler;
use Modules\Webhook\Application\UseCases\ReactivateWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\RegisterWebhookSubscriptionHandler;
use Modules\Webhook\Application\UseCases\RetryWebhookDeliveryHandler;
use Modules\Webhook\Application\UseCases\RotateWebhookSubscriptionSecretHandler;
use Modules\Webhook\Application\UseCases\SuspendWebhookSubscriptionHandler;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\Exceptions\InvalidWebhookSubscriptionTransition;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final class InMemoryWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    /** @var array<string, WebhookSubscription> */
    public array $items = [];

    public function save(WebhookSubscription $subscription): void
    {
        $this->items[$subscription->id()->value()] = $subscription;
    }

    public function findById(WebhookSubscriptionId $id): ?WebhookSubscription
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<WebhookSubscription> */
    public function findActiveByEvent(WebhookEventName $event): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (WebhookSubscription $subscription): bool => $subscription->isActive() && $subscription->subscribesTo($event),
        ));
    }

    /** @return list<WebhookSubscription> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class InMemoryWebhookDeliveryRepository implements WebhookDeliveryRepository
{
    /** @var array<string, WebhookDelivery> */
    public array $items = [];

    public function save(WebhookDelivery $delivery): void
    {
        $this->items[$delivery->id()] = $delivery;
    }

    public function findById(string $id): ?WebhookDelivery
    {
        return $this->items[$id] ?? null;
    }

    /** @return list<WebhookDelivery> */
    public function findBySubscription(WebhookSubscriptionId $subscriptionId, ?WebhookDeliveryStatus $status = null): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (WebhookDelivery $delivery): bool => $delivery->subscriptionId() === $subscriptionId->value()
                && ($status === null || $delivery->status() === $status),
        ));
    }
}

final class FakeAuditLoggerForWebhooks implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

final class FakeWebhookDeliveryDispatcher implements WebhookDeliveryDispatcher
{
    /** @var list<array{id: string, delay: int}> */
    public array $dispatched = [];

    public function dispatch(string $deliveryId, int $delaySeconds = 0): void
    {
        $this->dispatched[] = ['id' => $deliveryId, 'delay' => $delaySeconds];
    }
}

function persistedWebhookSubscriptionFor(InMemoryWebhookSubscriptionRepository $repository): WebhookSubscription
{
    $subscription = WebhookSubscription::register(
        id: WebhookSubscriptionId::fromString((string) Str::uuid()),
        url: 'https://example.test/webhooks',
        events: [WebhookEventName::EnrollmentCreated],
        secret: WebhookSigningSecret::generate(),
    );
    $repository->save($subscription);

    return $subscription;
}

it('registra una suscripcion nueva con un secreto en texto plano', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;

    $response = (new RegisterWebhookSubscriptionHandler($repository, $auditLogger))->handle(new RegisterWebhookSubscriptionCommand(
        url: 'https://example.test/webhooks',
        events: ['enrollment.created'],
        actorId: 'actor-1',
    ));

    expect($response)->toBeInstanceOf(WebhookSubscriptionResponse::class)
        ->and($response->url)->toBe('https://example.test/webhooks')
        ->and($response->events)->toBe(['enrollment.created'])
        ->and($response->status)->toBe('active')
        ->and($response->secret)->not->toBeNull()
        ->and($response->secret)->toMatch('/^[0-9a-f]{64}$/')
        ->and($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('webhook.subscription_registered')
        ->and($auditLogger->logged[0]->userId)->toBe('actor-1');
});

it('rechaza registrar una suscripcion con un nombre de evento invalido', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;

    expect(fn () => (new RegisterWebhookSubscriptionHandler($repository, $auditLogger))->handle(new RegisterWebhookSubscriptionCommand(
        url: 'https://example.test/webhooks',
        events: ['no.es.un.evento'],
        actorId: 'actor-1',
    )))->toThrow(InvalidWebhookEventName::class);

    expect($auditLogger->logged)->toBe([]);
});

it('suspende y reactiva una suscripcion existente auditando cada accion', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;
    $subscription = persistedWebhookSubscriptionFor($repository);
    $id = $subscription->id()->value();

    $suspended = (new SuspendWebhookSubscriptionHandler($repository, $auditLogger))->handle(new SuspendWebhookSubscriptionCommand($id, 'actor-1'));
    expect($suspended->status)->toBe('suspended');

    $reactivated = (new ReactivateWebhookSubscriptionHandler($repository, $auditLogger))->handle(new ReactivateWebhookSubscriptionCommand($id, 'actor-1'));
    expect($reactivated->status)->toBe('active');

    expect($auditLogger->logged)->toHaveCount(2)
        ->and($auditLogger->logged[0]->action)->toBe('webhook.subscription_suspended')
        ->and($auditLogger->logged[1]->action)->toBe('webhook.subscription_reactivated');
});

it('rechaza mutar una suscripcion inexistente', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;
    $id = (string) Str::uuid();

    expect(fn () => (new SuspendWebhookSubscriptionHandler($repository, $auditLogger))->handle(new SuspendWebhookSubscriptionCommand($id, 'actor-1')))
        ->toThrow(WebhookSubscriptionNotFound::class);
    expect(fn () => (new ReactivateWebhookSubscriptionHandler($repository, $auditLogger))->handle(new ReactivateWebhookSubscriptionCommand($id, 'actor-1')))
        ->toThrow(WebhookSubscriptionNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;
    $subscription = persistedWebhookSubscriptionFor($repository);

    expect(fn () => (new ReactivateWebhookSubscriptionHandler($repository, $auditLogger))->handle(new ReactivateWebhookSubscriptionCommand($subscription->id()->value(), 'actor-1')))
        ->toThrow(InvalidWebhookSubscriptionTransition::class);
});

it('rota el secreto y devuelve el nuevo valor en texto plano', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $auditLogger = new FakeAuditLoggerForWebhooks;
    $subscription = persistedWebhookSubscriptionFor($repository);
    $originalSecret = $subscription->secret()->value();

    $response = (new RotateWebhookSubscriptionSecretHandler($repository, $auditLogger))->handle(new RotateWebhookSubscriptionSecretCommand($subscription->id()->value(), 'actor-1'));

    expect($response->secret)->not->toBeNull()
        ->and($response->secret)->not->toBe($originalSecret)
        ->and($repository->findById($subscription->id())?->secret()->value())->toBe($response->secret);
});

it('consulta una suscripcion por id sin exponer el secreto', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    $subscription = persistedWebhookSubscriptionFor($repository);

    $response = (new GetWebhookSubscriptionHandler($repository))->handle(new GetWebhookSubscriptionQuery($subscription->id()->value()));

    expect($response->id)->toBe($subscription->id()->value())
        ->and($response->secret)->toBeNull();
});

it('rechaza consultar una suscripcion inexistente', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;

    expect(fn () => (new GetWebhookSubscriptionHandler($repository))->handle(new GetWebhookSubscriptionQuery((string) Str::uuid())))
        ->toThrow(WebhookSubscriptionNotFound::class);
});

it('lista todas las suscripciones registradas', function (): void {
    $repository = new InMemoryWebhookSubscriptionRepository;
    persistedWebhookSubscriptionFor($repository);
    persistedWebhookSubscriptionFor($repository);

    $responses = (new ListWebhookSubscriptionsHandler($repository))->handle(new ListWebhookSubscriptionsQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(WebhookSubscriptionResponse::class);
});

it('lista las entregas de una suscripcion, opcionalmente filtradas por estado', function (): void {
    $subscriptions = new InMemoryWebhookSubscriptionRepository;
    $deliveries = new InMemoryWebhookDeliveryRepository;
    $subscription = persistedWebhookSubscriptionFor($subscriptions);

    $delivered = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $delivered->markDelivered(200, 'ok', new DateTimeImmutable('now'));
    $deliveries->save($delivered);

    $failed = WebhookDelivery::create((string) Str::uuid(), $subscription->id()->value(), WebhookEventName::EnrollmentCreated, []);
    $failed->recordFailedAttempt(500, null, new DateTimeImmutable('now'));
    $deliveries->save($failed);

    $handler = new ListWebhookDeliveriesHandler($subscriptions, $deliveries);

    expect($handler->handle(new ListWebhookDeliveriesQuery($subscription->id()->value())))->toHaveCount(2)
        ->and($handler->handle(new ListWebhookDeliveriesQuery($subscription->id()->value(), 'failed')))->toHaveCount(1)
        ->and($handler->handle(new ListWebhookDeliveriesQuery($subscription->id()->value(), 'delivered')))->toHaveCount(1);
});

it('rechaza listar entregas de una suscripcion inexistente', function (): void {
    $subscriptions = new InMemoryWebhookSubscriptionRepository;
    $deliveries = new InMemoryWebhookDeliveryRepository;

    expect(fn () => (new ListWebhookDeliveriesHandler($subscriptions, $deliveries))->handle(new ListWebhookDeliveriesQuery((string) Str::uuid())))
        ->toThrow(WebhookSubscriptionNotFound::class);
});

it('reintenta manualmente una entrega fallida y la redespacha de inmediato', function (): void {
    $deliveries = new InMemoryWebhookDeliveryRepository;
    $dispatcher = new FakeWebhookDeliveryDispatcher;
    $delivery = WebhookDelivery::create((string) Str::uuid(), (string) Str::uuid(), WebhookEventName::EnrollmentCreated, []);
    $delivery->recordFailedAttempt(500, null, new DateTimeImmutable('now'));
    $deliveries->save($delivery);

    $response = (new RetryWebhookDeliveryHandler($deliveries, $dispatcher))->handle(new RetryWebhookDeliveryCommand($delivery->id(), 'actor-1'));

    expect($response)->toBeInstanceOf(WebhookDeliveryResponse::class)
        ->and($response->status)->toBe('pending')
        ->and($dispatcher->dispatched)->toHaveCount(1)
        ->and($dispatcher->dispatched[0]['id'])->toBe($delivery->id())
        ->and($dispatcher->dispatched[0]['delay'])->toBe(0);
});

it('rechaza reintentar manualmente una entrega inexistente', function (): void {
    $deliveries = new InMemoryWebhookDeliveryRepository;
    $dispatcher = new FakeWebhookDeliveryDispatcher;

    expect(fn () => (new RetryWebhookDeliveryHandler($deliveries, $dispatcher))->handle(new RetryWebhookDeliveryCommand((string) Str::uuid(), 'actor-1')))
        ->toThrow(WebhookDeliveryNotFound::class);
});
