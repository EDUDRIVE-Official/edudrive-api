<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\CancelExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class CancelExamAttemptHandler
{
    public function __construct(private ExamAttemptRepository $attempts) {}

    public function handle(CancelExamAttemptCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);

        $attempt->cancel();
        $this->attempts->save($attempt);

        return ExamAttemptResponse::fromAttempt($attempt, $this->questionMapper(true));
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
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
