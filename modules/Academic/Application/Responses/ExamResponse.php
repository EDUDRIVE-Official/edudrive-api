<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Entities\ExamQuestion;

final readonly class ExamResponse
{
    /**
     * @param  list<array{position: int, question_id: string, points: int, ref_id: string, type: string}>  $questions
     */
    private function __construct(
        public string $id,
        public string $title,
        public string $courseId,
        public ?string $description,
        public ?int $durationMinutes,
        public int $maxAttempts,
        public int $passingScore,
        public bool $shuffleQuestions,
        public string $feedbackMode,
        public array $questions,
    ) {}

    /**
     * @param  array<string, array{refId: string, type: string}>  $questionsByRefId
     */
    public static function fromExam(Exam $exam, array $questionsByRefId): self
    {
        return new self(
            $exam->id()->value(),
            $exam->title(),
            $exam->courseId()->value(),
            $exam->description(),
            $exam->durationMinutes(),
            $exam->maxAttempts(),
            $exam->passingScore(),
            $exam->shuffleQuestions(),
            $exam->feedbackMode()->value,
            array_map(
                static fn (ExamQuestion $examQuestion): array => [
                    'position' => $examQuestion->position(),
                    'question_id' => $examQuestion->questionId()->value(),
                    'points' => $examQuestion->points(),
                    'ref_id' => $questionsByRefId[$examQuestion->questionId()->value()]['refId'] ?? $examQuestion->questionId()->value(),
                    'type' => $questionsByRefId[$examQuestion->questionId()->value()]['type'] ?? '',
                ],
                $exam->questions(),
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     course_id: string,
     *     description: string|null,
     *     duration_minutes: int|null,
     *     max_attempts: int,
     *     passing_score: int,
     *     shuffle_questions: bool,
     *     feedback_mode: string,
     *     questions: list<array{position: int, question_id: string, points: int, ref_id: string, type: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'course_id' => $this->courseId,
            'description' => $this->description,
            'duration_minutes' => $this->durationMinutes,
            'max_attempts' => $this->maxAttempts,
            'passing_score' => $this->passingScore,
            'shuffle_questions' => $this->shuffleQuestions,
            'feedback_mode' => $this->feedbackMode,
            'questions' => $this->questions,
        ];
    }
}
