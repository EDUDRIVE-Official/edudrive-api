<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetRoadPassportQuery implements Query
{
    public function __construct(
        public string $roadPassportId,
        public string $userId,
        public bool $canViewOthers,
    ) {}
}
