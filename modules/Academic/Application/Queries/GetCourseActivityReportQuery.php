<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetCourseActivityReportQuery implements Query
{
    /** @param list<string> $courseIds */
    public function __construct(
        public array $courseIds = [],
    ) {}
}
