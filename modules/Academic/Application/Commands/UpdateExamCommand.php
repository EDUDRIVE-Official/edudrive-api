<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UpdateExamCommand implements Command
{
    /**
     * @param  list<array{questionId: string, points: int}>  $questions
     */
    public function __construct(
        public string $examId,
        public string $title,
        public ?string $description = null,
        public ?int $durationMinutes = null,
        public int $maxAttempts = 1,
        public int $passingScore = 60,
        public bool $shuffleQuestions = false,
        public string $feedbackMode = 'none',
        public string $kind = 'standard',
        public ?string $licenseCategory = null,
        public bool $allowPartialCredit = false,
        public bool $applyPenalties = false,
        public array $questions = [],
    ) {}
}
