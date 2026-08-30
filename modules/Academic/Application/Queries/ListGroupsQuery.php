<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListGroupsQuery implements Query
{
    public function __construct(
        public ?string $courseId = null,
    ) {}
}
