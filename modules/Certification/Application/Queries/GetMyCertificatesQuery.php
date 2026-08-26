<?php

declare(strict_types=1);

namespace Modules\Certification\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetMyCertificatesQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
