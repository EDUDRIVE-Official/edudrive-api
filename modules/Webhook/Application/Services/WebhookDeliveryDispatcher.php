<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Services;

interface WebhookDeliveryDispatcher
{
    public function dispatch(string $deliveryId, int $delaySeconds = 0): void;
}
