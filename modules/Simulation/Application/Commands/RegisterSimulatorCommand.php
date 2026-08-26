<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterSimulatorCommand implements Command
{
    public function __construct(
        public string $deviceIdentifier,
        public string $softwareVersion,
        public ?string $location = null,
    ) {}
}
