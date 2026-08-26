<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class IssueRoadPassportCommand implements Command
{
    public function __construct(
        public string $userId,
    ) {}
}
