<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class CourseExamAttemptsLookup
{
    public function __construct(
        private ExamRepository $exams,
        private ExamAttemptRepository $attempts,
    ) {}

    /** @return list<ExamAttempt> */
    public function submittedAttemptsFor(CourseId $courseId): array
    {
        $attempts = [];

        foreach ($this->exams->all($courseId) as $exam) {
            foreach ($this->attempts->all(examId: $exam->id(), status: ExamAttemptStatus::Submitted) as $attempt) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }
}
