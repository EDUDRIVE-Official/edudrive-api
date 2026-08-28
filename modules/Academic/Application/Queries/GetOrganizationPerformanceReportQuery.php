<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetOrganizationPerformanceReportQuery implements Query
{
    /** @param list<string> $organizationIds */
    public function __construct(
        public array $organizationIds = [],
    ) {}
}
