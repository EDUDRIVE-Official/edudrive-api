<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateInstitutionalEnrollmentCommand implements Command
{
    public function __construct(
        public string $courseId,
        public string $userId,
        public string $organizationId,
        public string $status = 'pending',
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
