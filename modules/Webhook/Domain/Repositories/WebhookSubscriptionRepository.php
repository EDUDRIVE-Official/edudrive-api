<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Repositories;

use Modules\Webhook\Domain\Aggregates\WebhookSubscription;
use Modules\Webhook\Domain\Enums\WebhookEventName;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

interface WebhookSubscriptionRepository
{
    public function save(WebhookSubscription $subscription): void;

    public function findById(WebhookSubscriptionId $id): ?WebhookSubscription;

    /** @return list<WebhookSubscription> */
    public function findActiveByEvent(WebhookEventName $event): array;

    /** @return list<WebhookSubscription> */
    public function all(): array;
}
