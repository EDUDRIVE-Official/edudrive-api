<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ReactivateRoadPassportCommand implements Command
{
    public function __construct(
        public string $roadPassportId,
    ) {}
}
