<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateGroupCommand implements Command
{
    public function __construct(
        public string $courseId,
        public ?string $organizationId,
        public string $name,
        public ?string $teacherId,
        public string $startsAt,
        public string $endsAt,
    ) {}
}
