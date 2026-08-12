<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

interface ExamAttemptRepository
{
    public function save(ExamAttempt $attempt): void;

    public function findById(ExamAttemptId $id): ?ExamAttempt;

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt;

    public function countCompletedFor(ExamId $examId, string $userId): int;

    /** @return list<ExamAttempt> */
    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array;
}
