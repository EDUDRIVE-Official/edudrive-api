<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RetireBadgeCommand implements Command
{
    public function __construct(
        public string $badgeId,
        public ?string $reason = null,
    ) {}
}
