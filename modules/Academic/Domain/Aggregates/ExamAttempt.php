<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final class ExamAttempt
{
    /** @param  list<AttemptQuestion>  $questions */
    private function __construct(
        private ExamAttemptId $id,
        private ExamId $examId,
        private string $userId,
        private ExamAttemptStatus $status,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $submittedAt,
        private string $title,
        private ?int $durationMinutes,
        private int $passingScore,
        private bool $shuffleQuestions,
        private ExamFeedbackMode $feedbackMode,
        private array $questions,
        private int $score,
        private int $totalPoints,
        private int $percentage,
        private bool $passed,
    ) {}

    /** @param  list<AttemptQuestion>  $questions
     *  @param  (callable(list<AttemptQuestion>): list<AttemptQuestion>)|null  $shuffler */
    public static function start(
        ExamAttemptId $id,
        ExamId $examId,
        string $userId,
        string $title,
        ?int $durationMinutes,
        int $passingScore,
        bool $shuffleQuestions,
        ExamFeedbackMode $feedbackMode,
        array $questions,
        DateTimeImmutable $startedAt,
        ?callable $shuffler = null,
    ): self {
        if ($shuffleQuestions) {
            $questions = ($shuffler ?? static function (array $items): array {
                shuffle($items);

                return $items;
            })($questions);
            $questions = array_map(
                static fn (AttemptQuestion $question, int $index): AttemptQuestion => $question->withPosition($index + 1),
                $questions,
                array_keys($questions),
            );
        }

        $totalPoints = array_sum(array_map(static fn (AttemptQuestion $question): int => $question->points(), $questions));

        $attempt = new self(
            $id,
            $examId,
            $userId,
            ExamAttemptStatus::InProgress,
            $startedAt,
            null,
            trim($title),
            $durationMinutes,
            $passingScore,
            $shuffleQuestions,
            $feedbackMode,
            $questions,
            0,
            $totalPoints,
            0,
            false,
        );
        $attempt->assertValid();

        return $attempt;
    }

    /** @param  list<AttemptQuestion>  $questions */
    public static function restore(
        ExamAttemptId $id,
        ExamId $examId,
        string $userId,
        ExamAttemptStatus $status,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $submittedAt,
        string $title,
        ?int $durationMinutes,
        int $passingScore,
        bool $shuffleQuestions,
        ExamFeedbackMode $feedbackMode,
        array $questions,
        int $score,
        int $totalPoints,
        int $percentage,
        bool $passed,
    ): self {
        return new self($id, $examId, $userId, $status, $startedAt, $submittedAt, $title, $durationMinutes, $passingScore, $shuffleQuestions, $feedbackMode, $questions, $score, $totalPoints, $percentage, $passed);
    }

    public function answer(int $position, QuestionResponse $response, DateTimeImmutable $answeredAt): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        $question = $this->questionAt($position);
        if ($question === null) {
            throw InvalidExamAttempt::create();
        }

        $question->answer($response, $answeredAt);
    }

    public function submit(DateTimeImmutable $submittedAt): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        if ($this->durationMinutes !== null
            && $submittedAt->getTimestamp() > $this->startedAt->getTimestamp() + $this->durationMinutes * 60
        ) {
            $this->status = ExamAttemptStatus::Canceled;
            $this->submittedAt = $submittedAt;

            return;
        }

        $score = 0;
        foreach ($this->questions as $question) {
            if ($question->isCorrect() === true) {
                $score += $question->points();
            }
        }
        $percentage = $this->totalPoints > 0 ? (int) round($score / $this->totalPoints * 100) : 0;

        $this->status = ExamAttemptStatus::Submitted;
        $this->submittedAt = $submittedAt;
        $this->score = $score;
        $this->percentage = $percentage;
        $this->passed = $percentage >= $this->passingScore;
    }

    public function cancel(): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        $this->status = ExamAttemptStatus::Canceled;
    }

    public function id(): ExamAttemptId
    {
        return $this->id;
    }

    public function examId(): ExamId
    {
        return $this->examId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function status(): ExamAttemptStatus
    {
        return $this->status;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function passingScore(): int
    {
        return $this->passingScore;
    }

    public function shuffleQuestions(): bool
    {
        return $this->shuffleQuestions;
    }

    public function feedbackMode(): ExamFeedbackMode
    {
        return $this->feedbackMode;
    }

    /** @return list<AttemptQuestion> */
    public function questions(): array
    {
        return $this->questions;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function totalPoints(): int
    {
        return $this->totalPoints;
    }

    public function percentage(): int
    {
        return $this->percentage;
    }

    public function passed(): bool
    {
        return $this->passed;
    }

    public function questionAt(int $position): ?AttemptQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->position() === $position) {
                return $question;
            }
        }

        return null;
    }

    private function assertValid(): void
    {
        if ($this->title === '' || $this->totalPoints < 1 || $this->questions === []) {
            throw InvalidExamAttempt::create();
        }
    }
}
