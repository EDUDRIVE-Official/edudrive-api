<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ReplaceProgramCoursesCommand implements Command
{
    /** @param list<string> $courseIds */
    public function __construct(
        public string $programId,
        public array $courseIds,
    ) {}
}
