<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetMyFilesQuery implements Query
{
    public function __construct(
        public string $ownerId,
    ) {}
}
