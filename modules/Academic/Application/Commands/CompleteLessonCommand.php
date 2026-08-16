<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CompleteLessonCommand implements Command
{
    public function __construct(
        public string $enrollmentId,
        public string $lessonId,
        public string $userId,
        public ?int $timeSpentMinutes = null,
    ) {}
}
