<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetRoadPassportByUserIdQuery implements Query
{
    public function __construct(
        public string $userId,
    ) {}
}
