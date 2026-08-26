<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SuspendRoadPassportCommand implements Command
{
    public function __construct(
        public string $roadPassportId,
        public ?string $reason = null,
    ) {}
}
