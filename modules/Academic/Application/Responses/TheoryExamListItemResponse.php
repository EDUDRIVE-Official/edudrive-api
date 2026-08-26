<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Exam;

final readonly class TheoryExamListItemResponse
{
    public function __construct(
        public string $id,
        public string $title,
        public string $courseId,
        public int $questionCount,
        public int $passingScore,
        public string $kind,
        public ?string $licenseCategory,
    ) {}

    public static function fromExam(Exam $exam): self
    {
        return new self(
            $exam->id()->value(),
            $exam->title(),
            $exam->courseId()->value(),
            count($exam->questions()),
            $exam->passingScore(),
            $exam->kind()->value,
            $exam->licenseCategory()?->value(),
        );
    }

    /** @return array{id: string, title: string, course_id: string, question_count: int, passing_score: int, kind: string, license_category: string|null} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'course_id' => $this->courseId,
            'question_count' => $this->questionCount,
            'passing_score' => $this->passingScore,
            'kind' => $this->kind,
            'license_category' => $this->licenseCategory,
        ];
    }
}
