<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptLimitReached;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final readonly class StartExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ExamRepository $exams,
        private QuestionRepository $questions,
    ) {}

    public function handle(StartExamAttemptCommand $command): ExamAttemptResponse
    {
        $examId = ExamId::fromString($command->examId);
        $exam = $this->exams->findById($examId);
        if ($exam === null) {
            throw ExamNotFound::withId($command->examId);
        }

        if ($this->attempts->findActiveFor($examId, $command->userId) !== null
            || $this->attempts->countCompletedFor($examId, $command->userId) >= $exam->maxAttempts()
        ) {
            throw ExamAttemptLimitReached::create();
        }

        $attempt = ExamAttempt::start(
            ExamAttemptId::fromString((string) Str::uuid()),
            $examId,
            $command->userId,
            $exam->title(),
            $exam->durationMinutes(),
            $exam->passingScore(),
            $exam->shuffleQuestions(),
            $exam->feedbackMode(),
            $this->buildSnapshot($exam),
            new DateTimeImmutable('now'),
        );
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(false));
    }

    /** @return list<AttemptQuestion> */
    private function buildSnapshot(Exam $exam): array
    {
        $questions = [];
        foreach ($exam->questions() as $examQuestion) {
            $question = $this->questions->findById($examQuestion->questionId());
            if (! $question instanceof Question) {
                continue;
            }

            $questions[] = AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                $examQuestion->position(),
                $examQuestion->questionId(),
                $question->competencyId(),
                $examQuestion->points(),
                $question->prompt(),
                $question->type(),
                array_map(
                    static fn (QuestionOption $option): array => [
                        'refId' => $option->refId(),
                        'id' => $option->id()->value(),
                        'label' => $option->label(),
                        'position' => $option->position(),
                        'side' => $option->side(),
                    ],
                    $question->options(),
                ),
                $question->response(),
                $question->explanation(),
            );
        }

        return $questions;
    }

    /** @return callable(AttemptQuestion): array<string, mixed> */
    private function questionMapper(bool $showFeedback): callable
    {
        return static function (AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }
}
