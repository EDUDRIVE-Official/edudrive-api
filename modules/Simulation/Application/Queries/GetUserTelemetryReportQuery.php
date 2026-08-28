<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetUserTelemetryReportQuery implements Query
{
    /** @param list<string> $userIds */
    public function __construct(
        public array $userIds = [],
    ) {}
}
