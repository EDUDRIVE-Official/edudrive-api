<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Services;

use Modules\Webhook\Application\Services\WebhookDeliveryDispatcher;
use Modules\Webhook\Infrastructure\Jobs\DeliverWebhookJob;

final readonly class LaravelWebhookDeliveryDispatcher implements WebhookDeliveryDispatcher
{
    public function dispatch(string $deliveryId, int $delaySeconds = 0): void
    {
        DeliverWebhookJob::dispatch($deliveryId)->delay(now()->addSeconds($delaySeconds));
    }
}
