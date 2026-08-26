<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class GrantAchievementCommand implements Command
{
    public function __construct(
        public string $achievementId,
        public string $userId,
        public string $evidence,
    ) {}
}
