<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\UpdateExamCommand;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class UpdateExamHandler
{
    public function __construct(
        private ExamRepository $exams,
        private QuestionRepository $questions,
    ) {}

    public function handle(UpdateExamCommand $command): ExamResponse
    {
        $exam = $this->exams->findById(ExamId::fromString($command->examId));
        if ($exam === null) {
            throw ExamNotFound::withId($command->examId);
        }

        [$examQuestions, $questionDetails] = $this->buildExamQuestions($command->questions);
        $exam->replace(
            title: $command->title,
            questions: $examQuestions,
            description: $command->description,
            durationMinutes: $command->durationMinutes,
            maxAttempts: $command->maxAttempts,
            passingScore: $command->passingScore,
            shuffleQuestions: $command->shuffleQuestions,
            feedbackMode: ExamFeedbackMode::from($command->feedbackMode),
        );
        $this->exams->save($exam);

        return ExamResponse::fromExam($exam, $questionDetails);
    }

    /**
     * @param  list<array{questionId: string, points: int}>  $payloads
     * @return array{0: list<ExamQuestion>, 1: array<string, array{refId: string, type: string}>}
     */
    private function buildExamQuestions(array $payloads): array
    {
        $examQuestions = [];
        $details = [];
        foreach ($payloads as $index => $payload) {
            $questionId = QuestionId::fromString((string) $payload['questionId']);
            $question = $this->questions->findById($questionId);
            if ($question === null) {
                throw QuestionNotFound::withId((string) $payload['questionId']);
            }
            $details[$questionId->value()] = [
                'refId' => $question->id()->value(),
                'type' => $question->type()->value,
            ];
            $examQuestions[] = ExamQuestion::create($index + 1, $questionId, (int) $payload['points']);
        }

        return [$examQuestions, $details];
    }
}
