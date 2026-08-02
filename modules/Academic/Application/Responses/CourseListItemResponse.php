<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class CourseListItemResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public ?string $description,
        public ?string $objectives,
        public ?string $prerequisites,
        public ?string $modality,
        public ?int $durationHours,
        public string $status,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            description: $course->description(),
            objectives: $course->objectives(),
            prerequisites: $course->prerequisites(),
            modality: $course->modality()?->value,
            durationHours: $course->durationHours(),
            status: $course->status()->value,
        );
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     description: string|null,
     *     objectives: string|null,
     *     prerequisites: string|null,
     *     modality: string|null,
     *     duration_hours: int|null,
     *     status: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'prerequisites' => $this->prerequisites,
            'modality' => $this->modality,
            'duration_hours' => $this->durationHours,
            'status' => $this->status,
        ];
    }
}
