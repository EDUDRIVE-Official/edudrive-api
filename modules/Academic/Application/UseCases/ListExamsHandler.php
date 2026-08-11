<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListExamsQuery;
use Modules\Academic\Application\Responses\ExamListItemResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class ListExamsHandler
{
    public function __construct(private ExamRepository $exams) {}

    /** @return list<ExamListItemResponse> */
    public function handle(ListExamsQuery $query): array
    {
        $courseId = $query->courseId === null ? null : CourseId::fromString($query->courseId);

        return array_map(
            static fn (Exam $exam): ExamListItemResponse => ExamListItemResponse::fromExam($exam),
            $this->exams->all($courseId),
        );
    }
}
