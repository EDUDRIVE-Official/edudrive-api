<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateEnrollmentCommand implements Command
{
    public function __construct(
        public string $courseId,
        public string $userId,
        public string $status = 'pending',
        public string $source = 'individual',
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
