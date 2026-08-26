<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class StartSimulationSessionCommand implements Command
{
    public function __construct(
        public string $sessionId,
        public string $userId,
        public bool $canManageOthers,
    ) {}
}
