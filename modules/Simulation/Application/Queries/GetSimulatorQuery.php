<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetSimulatorQuery implements Query
{
    public function __construct(
        public string $simulatorId,
    ) {}
}
