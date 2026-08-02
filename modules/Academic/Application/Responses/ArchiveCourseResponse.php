<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class ArchiveCourseResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
        public string $archivedAt,
    ) {}

    public static function fromCourse(Course $course): self
    {
        $archivedAt = $course->archivedAt();

        assert($archivedAt !== null);

        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
            archivedAt: $archivedAt->format(DATE_ATOM),
        );
    }

    /**
     * @return array{id: string, code: string, title: string, status: string, archived_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'archived_at' => $this->archivedAt,
        ];
    }
}
