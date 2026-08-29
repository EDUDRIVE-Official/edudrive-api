<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Modules\Webhook\Application\Queries\ListWebhookSubscriptionsQuery;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;

final readonly class ListWebhookSubscriptionsHandler
{
    public function __construct(private WebhookSubscriptionRepository $subscriptions) {}

    /** @return list<WebhookSubscriptionResponse> */
    public function handle(ListWebhookSubscriptionsQuery $query): array
    {
        return array_map(
            static fn (WebhookSubscription $subscription): WebhookSubscriptionResponse => WebhookSubscriptionResponse::fromSubscription($subscription),
            $this->subscriptions->all(),
        );
    }
}
