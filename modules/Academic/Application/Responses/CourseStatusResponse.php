<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class CourseStatusResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
        );
    }

    /**
     * @return array{id: string, code: string, title: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
        ];
    }
}
