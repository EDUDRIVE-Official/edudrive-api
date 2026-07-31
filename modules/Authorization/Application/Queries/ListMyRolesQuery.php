<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListMyRolesQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
