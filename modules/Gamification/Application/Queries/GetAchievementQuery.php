<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetAchievementQuery implements Query
{
    public function __construct(
        public string $achievementId,
    ) {}
}
