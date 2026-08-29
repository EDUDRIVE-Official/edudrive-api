<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RetryWebhookDeliveryCommand implements Command
{
    public function __construct(
        public string $deliveryId,
        public string $actorId,
    ) {}
}
