<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetOrganizationQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
