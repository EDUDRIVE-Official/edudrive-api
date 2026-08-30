<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Queries;

final readonly class GetMyTeacherProfileQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
