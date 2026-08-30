<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class AssignGroupTeacherCommand implements Command
{
    public function __construct(
        public string $groupId,
        public ?string $teacherId,
    ) {}
}
