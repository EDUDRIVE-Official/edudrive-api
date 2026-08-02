<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;

final readonly class PublishCourseResponse
{
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
        public string $publishedAt,
    ) {}

    public static function fromCourse(Course $course): self
    {
        $publishedAt = $course->publishedAt();

        assert($publishedAt !== null);

        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
            publishedAt: $publishedAt->format(DATE_ATOM),
        );
    }

    /**
     * @return array{id: string, code: string, title: string, status: string, published_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
        ];
    }
}
