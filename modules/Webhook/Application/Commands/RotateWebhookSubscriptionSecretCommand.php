<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RotateWebhookSubscriptionSecretCommand implements Command
{
    public function __construct(
        public string $subscriptionId,
        public string $actorId,
    ) {}
}
