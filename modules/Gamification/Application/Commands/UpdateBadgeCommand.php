<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UpdateBadgeCommand implements Command
{
    public function __construct(
        public string $badgeId,
        public string $name,
        public string $description,
        public string $criteria,
        public string $category,
        public string $level,
    ) {}
}
