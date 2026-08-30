<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Queries;

final readonly class GetLinkedMinorProgressQuery
{
    public function __construct(
        public string $guardianUserId,
        public string $minorUserId,
    ) {}
}
