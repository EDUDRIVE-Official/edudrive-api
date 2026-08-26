<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class ListTheoryExamAttemptsQuery implements Query
{
    public function __construct(
        public ?string $userId = null,
        public ?string $targetUserId = null,
        public ?string $licenseCategory = null,
    ) {}
}
