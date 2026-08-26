<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\GradingResult;

final class ExamAttempt
{
    /** @param  list<AttemptQuestion>  $questions
     *  @param  list<AttemptQuestionGrade>  $questionBreakdown
     *  @param  list<CompetencyGrade>  $competencyBreakdown */
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
        private array $questionBreakdown,
        private array $competencyBreakdown,
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
            [],
            [],
        );
        $attempt->assertValid();

        return $attempt;
    }

    /** @param  list<AttemptQuestion>  $questions
     *  @param  list<AttemptQuestionGrade>  $questionBreakdown
     *  @param  list<CompetencyGrade>  $competencyBreakdown */
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
        array $questionBreakdown = [],
        array $competencyBreakdown = [],
    ): self {
        return new self($id, $examId, $userId, $status, $startedAt, $submittedAt, $title, $durationMinutes, $passingScore, $shuffleQuestions, $feedbackMode, $questions, $score, $totalPoints, $percentage, $passed, $questionBreakdown, $competencyBreakdown);
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

    public function submit(DateTimeImmutable $submittedAt, ?GradingResult $gradingResult = null): void
    {
        if ($this->status !== ExamAttemptStatus::InProgress) {
            throw InvalidExamAttempt::create();
        }

        if ($this->hasTimedOutAt($submittedAt)) {
            $this->status = ExamAttemptStatus::Canceled;
            $this->submittedAt = $submittedAt;

            return;
        }

        if ($gradingResult === null) {
            throw InvalidExamAttempt::create();
        }

        $this->status = ExamAttemptStatus::Submitted;
        $this->submittedAt = $submittedAt;
        $this->score = $gradingResult->score();
        $this->totalPoints = $gradingResult->totalPoints();
        $this->percentage = $gradingResult->percentage();
        $this->passed = $gradingResult->passed();
        $this->questionBreakdown = $gradingResult->questionBreakdown();
        $this->competencyBreakdown = $gradingResult->competencyBreakdown();
    }

    public function hasTimedOutAt(DateTimeImmutable $submittedAt): bool
    {
        return $this->durationMinutes !== null
            && $submittedAt->getTimestamp() > $this->startedAt->getTimestamp() + $this->durationMinutes * 60;
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

    /** @return list<AttemptQuestionGrade> */
    public function questionBreakdown(): array
    {
        return $this->questionBreakdown;
    }

    /** @return list<CompetencyGrade> */
    public function competencyBreakdown(): array
    {
        return $this->competencyBreakdown;
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
