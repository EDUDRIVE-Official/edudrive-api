<?php

declare(strict_types=1);

namespace Modules\Learning\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetEnrollmentLearningEventsQuery implements Query
{
    public function __construct(
        public string $enrollmentId,
        public string $userId,
        public bool $canViewOthers,
    ) {}
}
