<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Queries;

final readonly class GetMyStudentProfileQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
