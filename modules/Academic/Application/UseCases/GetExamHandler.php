<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class GetExamHandler
{
    public function __construct(
        private ExamRepository $exams,
        private ?QuestionRepository $questions = null,
    ) {}

    public function handle(GetExamQuery $query): ExamResponse
    {
        $exam = $this->exams->findById(ExamId::fromString($query->examId));
        if ($exam === null) {
            throw ExamNotFound::withId($query->examId);
        }

        return ExamResponse::fromExam($exam, $this->questionDetails($exam, $this->questions ?? app(QuestionRepository::class)));
    }

    /** @return array<string, array{refId: string, type: string}> */
    private function questionDetails(Exam $exam, QuestionRepository $questions): array
    {
        $details = [];
        foreach ($exam->questions() as $examQuestion) {
            $question = $questions->findById($examQuestion->questionId());
            if ($question !== null) {
                $details[$examQuestion->questionId()->value()] = [
                    'refId' => $question->id()->value(),
                    'type' => $question->type()->value,
                ];
            }
        }

        return $details;
    }
}
