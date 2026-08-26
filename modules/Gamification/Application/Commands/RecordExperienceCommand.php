<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RecordExperienceCommand implements Command
{
    public function __construct(
        public string $userId,
        public int $points,
        public ?string $competencyId,
        public string $reason,
    ) {}
}
