<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetPracticalResultQuery implements Query
{
    public function __construct(
        public string $sessionId,
        public string $userId,
        public bool $canViewOthers,
    ) {}
}
