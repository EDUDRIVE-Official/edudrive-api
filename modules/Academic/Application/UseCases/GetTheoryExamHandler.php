<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\InvalidTheoryExam;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Queries\GetTheoryExamQuery;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class GetTheoryExamHandler
{
    public function __construct(
        private ExamRepository $exams,
        private QuestionRepository $questions,
    ) {}

    public function handle(GetTheoryExamQuery $query): ExamResponse
    {
        $exam = $this->exams->findById(ExamId::fromString($query->examId));
        if ($exam === null || $exam->kind() !== ExamKind::Theory) {
            throw InvalidTheoryExam::create();
        }

        return (new GetExamHandler($this->exams, $this->questions))->handle(new GetExamQuery($query->examId));
    }
}
