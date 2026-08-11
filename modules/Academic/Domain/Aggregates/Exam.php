<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamId;

final class Exam
{
    private const int MAX_TITLE_LENGTH = 180;

    private const int MAX_DESCRIPTION_LENGTH = 2000;

    /** @param list<ExamQuestion> $questions */
    private function __construct(
        private ExamId $id,
        private CourseId $courseId,
        private string $title,
        private ?string $description,
        private ?int $durationMinutes,
        private int $maxAttempts,
        private int $passingScore,
        private bool $shuffleQuestions,
        private ExamFeedbackMode $feedbackMode,
        private array $questions,
    ) {}

    /** @param list<ExamQuestion> $questions */
    public static function create(
        ExamId $id,
        CourseId $courseId,
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): self {
        $exam = new self($id, $courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $exam->assertValid();

        return $exam;
    }

    /** @param list<ExamQuestion> $questions */
    public static function restore(
        ExamId $id,
        CourseId $courseId,
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): self {
        $exam = new self($id, $courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $exam->assertValid();

        return $exam;
    }

    /** @param list<ExamQuestion> $questions */
    public function replace(
        string $title,
        array $questions,
        ?string $description = null,
        ?int $durationMinutes = null,
        int $maxAttempts = 1,
        int $passingScore = 60,
        bool $shuffleQuestions = false,
        ExamFeedbackMode $feedbackMode = ExamFeedbackMode::None,
    ): void {
        $next = new self($this->id, $this->courseId, $title, $description, $durationMinutes, $maxAttempts, $passingScore, $shuffleQuestions, $feedbackMode, $questions);
        $next->assertValid();

        $this->title = $next->title;
        $this->description = $next->description;
        $this->durationMinutes = $next->durationMinutes;
        $this->maxAttempts = $next->maxAttempts;
        $this->passingScore = $next->passingScore;
        $this->shuffleQuestions = $next->shuffleQuestions;
        $this->feedbackMode = $next->feedbackMode;
        $this->questions = $next->questions;
    }

    public function id(): ExamId
    {
        return $this->id;
    }

    public function courseId(): CourseId
    {
        return $this->courseId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
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

    /** @return list<ExamQuestion> */
    public function questions(): array
    {
        return $this->questions;
    }

    private function assertValid(): void
    {
        $this->title = trim($this->title);
        if ($this->title === '' || strlen($this->title) > self::MAX_TITLE_LENGTH) {
            throw InvalidExam::create();
        }

        $this->description = self::optionalString($this->description, self::MAX_DESCRIPTION_LENGTH);

        if ($this->durationMinutes !== null && $this->durationMinutes < 1) {
            throw InvalidExam::create();
        }

        if ($this->maxAttempts < 1) {
            throw InvalidExam::create();
        }

        if ($this->passingScore < 1 || $this->passingScore > 100) {
            throw InvalidExam::create();
        }

        if ($this->questions === []) {
            throw InvalidExam::create();
        }

        $questionIds = array_map(
            static fn (ExamQuestion $question): string => $question->questionId()->value(),
            $this->questions,
        );
        if (count(array_unique($questionIds)) !== count($questionIds)) {
            throw InvalidExam::create();
        }

        $positions = array_map(static fn (ExamQuestion $question): int => $question->position(), $this->questions);
        if ($positions !== range(1, count($positions))) {
            throw InvalidExam::create();
        }
    }

    private static function optionalString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > $maxLength) {
            throw InvalidExam::create();
        }

        return $value;
    }
}
