<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListExamAttemptsQuery implements Query
{
    public function __construct(
        public ?string $examId = null,
        public ?string $userId = null,
        public ?string $status = null,
    ) {}
}
