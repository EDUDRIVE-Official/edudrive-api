<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SendNotificationCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $channel,
        public string $category,
        public string $subject,
        public string $body,
    ) {}
}
