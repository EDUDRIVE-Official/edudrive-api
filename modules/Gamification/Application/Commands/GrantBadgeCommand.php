<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class GrantBadgeCommand implements Command
{
    public function __construct(
        public string $badgeId,
        public string $userId,
        public string $evidence,
    ) {}
}
