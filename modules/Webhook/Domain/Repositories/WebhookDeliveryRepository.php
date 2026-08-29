<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\Repositories;

use Modules\Webhook\Domain\Entities\WebhookDelivery;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;
use Modules\Webhook\Domain\ValueObjects\WebhookSubscriptionId;

interface WebhookDeliveryRepository
{
    public function save(WebhookDelivery $delivery): void;

    public function findById(string $id): ?WebhookDelivery;

    /** @return list<WebhookDelivery> */
    public function findBySubscription(WebhookSubscriptionId $subscriptionId, ?WebhookDeliveryStatus $status = null): array;
}
