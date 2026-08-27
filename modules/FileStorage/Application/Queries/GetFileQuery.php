<?php

declare(strict_types=1);

namespace Modules\FileStorage\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetFileQuery implements Query
{
    public function __construct(
        public string $fileId,
        public string $requestingUserId,
        public bool $canViewOthers,
    ) {}
}
