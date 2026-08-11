<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateExamCommand implements Command
{
    /**
     * @param  list<array{questionId: string, points: int}>  $questions
     */
    public function __construct(
        public string $courseId,
        public string $title,
        public ?string $description = null,
        public ?int $durationMinutes = null,
        public int $maxAttempts = 1,
        public int $passingScore = 60,
        public bool $shuffleQuestions = false,
        public string $feedbackMode = 'none',
        public array $questions = [],
    ) {}
}
