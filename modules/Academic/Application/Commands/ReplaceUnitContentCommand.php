<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Academic\Application\DTO\LessonInput;
use Modules\Foundation\Application\Commands\Command;

final readonly class ReplaceUnitContentCommand implements Command
{
    /** @param list<LessonInput> $lessons */
    public function __construct(
        public string $courseId,
        public string $unitId,
        public array $lessons,
    ) {}
}
