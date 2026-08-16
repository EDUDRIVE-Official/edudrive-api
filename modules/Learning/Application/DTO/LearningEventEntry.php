<?php

declare(strict_types=1);

namespace Modules\Learning\Application\DTO;

use Modules\Learning\Domain\ValueObjects\LearningVerb;

final readonly class LearningEventEntry
{
    /** @param array<string, mixed> $evidence */
    public function __construct(
        public string $enrollmentId,
        public string $userId,
        public string $courseId,
        public LearningVerb $verb,
        public string $subjectId,
        public array $evidence,
    ) {}
}
