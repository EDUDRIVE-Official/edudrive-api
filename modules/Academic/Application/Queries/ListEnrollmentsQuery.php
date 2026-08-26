<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListEnrollmentsQuery implements Query
{
    public function __construct(
        public ?string $courseId = null,
        public ?string $userId = null,
        public ?string $organizationId = null,
        public ?string $status = null,
        public ?string $source = null,
    ) {}
}
