<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Queries\ListTheoryExamsQuery;
use Modules\Academic\Application\Responses\TheoryExamListItemResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\ExamRepository;

final readonly class ListTheoryExamsHandler
{
    public function __construct(private ExamRepository $exams) {}

    /** @return list<TheoryExamListItemResponse> */
    public function handle(ListTheoryExamsQuery $query): array
    {
        return array_values(array_map(
            static fn (Exam $exam): TheoryExamListItemResponse => TheoryExamListItemResponse::fromExam($exam),
            array_filter(
                $this->exams->all(),
                static fn (Exam $exam): bool => $exam->kind() === ExamKind::Theory,
            ),
        ));
    }
}
