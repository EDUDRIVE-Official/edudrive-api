<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Exam;

final readonly class ExamListItemResponse
{
    private function __construct(
        public string $id,
        public string $title,
        public string $courseId,
        public int $questionCount,
        public int $passingScore,
    ) {}

    public static function fromExam(Exam $exam): self
    {
        return new self(
            $exam->id()->value(),
            $exam->title(),
            $exam->courseId()->value(),
            count($exam->questions()),
            $exam->passingScore(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     course_id: string,
     *     question_count: int,
     *     passing_score: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'course_id' => $this->courseId,
            'question_count' => $this->questionCount,
            'passing_score' => $this->passingScore,
        ];
    }
}
