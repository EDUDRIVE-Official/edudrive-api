<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetExamQuery implements Query
{
    public function __construct(public string $examId) {}
}
