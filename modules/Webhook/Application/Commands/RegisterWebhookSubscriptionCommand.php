<?php

declare(strict_types=1);

namespace Modules\Webhook\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterWebhookSubscriptionCommand implements Command
{
    /** @param list<string> $events */
    public function __construct(
        public string $url,
        public array $events,
        public string $actorId,
    ) {}
}
