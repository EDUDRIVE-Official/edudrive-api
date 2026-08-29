<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetAsyncJobQuery implements Query
{
    public function __construct(
        public string $asyncJobId,
        public ?string $requestedByUserId,
    ) {}
}
