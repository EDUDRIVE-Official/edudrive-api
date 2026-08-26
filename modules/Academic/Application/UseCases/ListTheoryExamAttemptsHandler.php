<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListTheoryExamAttemptsQuery;
use Modules\Academic\Application\Responses\TheoryExamAttemptListItemResponse;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;

final readonly class ListTheoryExamAttemptsHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ExamRepository $exams,
    ) {}

    /** @return list<TheoryExamAttemptListItemResponse> */
    public function handle(ListTheoryExamAttemptsQuery $query): array
    {
        $userId = $query->targetUserId ?? $query->userId;
        $results = [];

        foreach ($this->attempts->all(userId: $userId) as $attempt) {
            $exam = $this->exams->findById($attempt->examId());
            if ($exam === null || $exam->kind() !== ExamKind::Theory || $exam->licenseCategory() === null) {
                continue;
            }

            if ($query->licenseCategory !== null && $exam->licenseCategory()->value() !== strtoupper(trim($query->licenseCategory))) {
                continue;
            }

            $results[] = new TheoryExamAttemptListItemResponse(
                $attempt->id()->value(),
                $attempt->examId()->value(),
                $attempt->userId(),
                $attempt->status()->value,
                $attempt->startedAt()->format(DATE_ATOM),
                $attempt->submittedAt()?->format(DATE_ATOM),
                $attempt->score(),
                $attempt->percentage(),
                $attempt->passed(),
                $exam->licenseCategory()->value(),
            );
        }

        return $results;
    }
}
