<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ReactivateApiConsumerCommand implements Command
{
    public function __construct(
        public string $consumerId,
        public string $actorId,
    ) {}
}
