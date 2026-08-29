<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateBulkInstitutionalEnrollmentsCommand implements Command
{
    /** @param list<string> $userIds */
    public function __construct(
        public string $courseId,
        public string $organizationId,
        public array $userIds,
        public string $status = 'pending',
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
