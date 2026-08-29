<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Services;

use Illuminate\Support\Str;
use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Application\Services\WebhookEventPublisher;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookEvent;

final readonly class QueuedWebhookEventPublisher implements WebhookEventPublisher
{
    public function __construct(
        private WebhookSubscriptionRepository $subscriptions,
        private WebhookDeliveryRepository $deliveries,
        private WebhookDeliveryDispatcher $dispatcher,
    ) {}

    public function publish(WebhookEvent $event): void
    {
        foreach ($this->subscriptions->findActiveByEvent($event->name) as $subscription) {
            $delivery = WebhookDelivery::create(
                id: (string) Str::uuid(),
                subscriptionId: $subscription->id()->value(),
                eventName: $event->name,
                payload: $event->payload,
                createdAt: $event->occurredAt,
            );

            $this->deliveries->save($delivery);
            $this->dispatcher->dispatch($delivery->id());
        }
    }
}
