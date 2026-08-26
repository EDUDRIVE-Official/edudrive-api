<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SuspendSimulatorCommand implements Command
{
    public function __construct(
        public string $simulatorId,
        public ?string $reason = null,
    ) {}
}
