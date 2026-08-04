<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Foundation\Application\Commands\Command;

final readonly class ReplaceCourseCurriculumCommand implements Command
{
    /** @param list<CourseModuleInput> $modules */
    public function __construct(
        public string $courseId,
        public array $modules,
    ) {}
}
