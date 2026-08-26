<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class ListExamAttemptsHandler
{
    public function __construct(private ExamAttemptRepository $attempts) {}

    /** @return list<ExamAttemptListItemResponse> */
    public function handle(ListExamAttemptsQuery $query): array
    {
        return array_map(
            static fn (ExamAttempt $attempt): ExamAttemptListItemResponse => ExamAttemptListItemResponse::fromAttempt($attempt),
            $this->attempts->all(
                $query->examId === null ? null : ExamId::fromString($query->examId),
                $query->userId,
                $query->status === null ? null : ExamAttemptStatus::from($query->status),
            ),
        );
    }
}
