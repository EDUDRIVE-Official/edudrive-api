<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;

interface ExamRepository
{
    public function save(Exam $exam): void;

    public function findById(ExamId $id): ?Exam;

    /** @return list<Exam> */
    public function all(?CourseId $courseId = null): array;

    public function delete(ExamId $id): void;
}
