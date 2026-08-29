<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\UseCases;

use Modules\Webhook\Application\Exceptions\WebhookSubscriptionNotFound;
use Modules\Webhook\Application\Queries\GetWebhookSubscriptionQuery;
use Modules\Webhook\Application\Responses\WebhookSubscriptionResponse;
use Modules\Webhook\Domain\Repositories\WebhookSubscriptionRepository;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

final readonly class GetWebhookSubscriptionHandler
{
    public function __construct(private WebhookSubscriptionRepository $subscriptions) {}

    public function handle(GetWebhookSubscriptionQuery $query): WebhookSubscriptionResponse
    {
        $subscription = $this->subscriptions->findById(WebhookSubscriptionId::fromString($query->subscriptionId));
        if ($subscription === null) {
            throw WebhookSubscriptionNotFound::withId($query->subscriptionId);
        }

        return WebhookSubscriptionResponse::fromSubscription($subscription);
    }
}
