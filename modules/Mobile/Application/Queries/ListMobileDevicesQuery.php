<?php

declare(strict_types=1);

namespace Modules\Mobile\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListMobileDevicesQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
