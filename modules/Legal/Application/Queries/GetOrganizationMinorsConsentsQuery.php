<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetOrganizationMinorsConsentsQuery implements Query
{
    public function __construct(
        public string $organizationId,
    ) {}
}
