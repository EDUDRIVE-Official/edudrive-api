<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Modules\Webhook\Application\Exceptions\WebhookSubscriptionNotFound;
use Modules\Webhook\Application\Queries\ListWebhookDeliveriesQuery;
use Modules\Webhook\Application\Responses\WebhookDeliveryResponse;
use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\Repositories\WebhookDeliveryRepository;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final readonly class ListWebhookDeliveriesHandler
{
    public function __construct(
        private WebhookSubscriptionRepository $subscriptions,
        private WebhookDeliveryRepository $deliveries,
    ) {}

    /** @return list<WebhookDeliveryResponse> */
    public function handle(ListWebhookDeliveriesQuery $query): array
    {
        $subscriptionId = WebhookSubscriptionId::fromString($query->subscriptionId);
        if ($this->subscriptions->findById($subscriptionId) === null) {
            throw WebhookSubscriptionNotFound::withId($query->subscriptionId);
        }

        $status = $query->status === null ? null : WebhookDeliveryStatus::from($query->status);

        return array_map(
            static fn (WebhookDelivery $delivery): WebhookDeliveryResponse => WebhookDeliveryResponse::fromDelivery($delivery),
            $this->deliveries->findBySubscription($subscriptionId, $status),
        );
    }
}
