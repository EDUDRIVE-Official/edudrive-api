<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\ValueObjects\CourseId;

final readonly class UnitContentResponse
{
    /**
     * @param list<array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     summary: string|null,
     *     duration_minutes: int|null,
     *     position: int,
     *     blocks: list<array{
     *         id: string,
     *         type: string,
     *         position: int,
     *         payload: array<string, mixed>
     *     }>
     * }> $lessons
     */
    private function __construct(
        public string $courseId,
        public string $unitId,
        public string $courseStatus,
        private array $lessons,
    ) {}

    public static function fromUnitContent(
        CourseId $courseId,
        CourseStatus $courseStatus,
        UnitContent $content,
    ): self {
        return new self(
            courseId: $courseId->value(),
            unitId: $content->unitId()->value(),
            courseStatus: $courseStatus->value,
            lessons: array_map(
                static fn (Lesson $lesson): array => [
                    'id' => $lesson->id()->value(),
                    'code' => $lesson->code()->value(),
                    'title' => $lesson->title(),
                    'summary' => $lesson->summary(),
                    'duration_minutes' => $lesson->durationMinutes(),
                    'position' => $lesson->position(),
                    'blocks' => array_map(
                        static fn (ContentBlock $block): array => [
                            'id' => $block->id()->value(),
                            'type' => $block->type()->value,
                            'position' => $block->position(),
                            'payload' => $block->payload(),
                        ],
                        $lesson->blocks(),
                    ),
                ],
                $content->lessons(),
            ),
        );
    }

    /**
     * @return array{
     *     course_id: string,
     *     unit_id: string,
     *     course_status: string,
     *     lessons: list<array{
     *         id: string,
     *         code: string,
     *         title: string,
     *         summary: string|null,
     *         duration_minutes: int|null,
     *         position: int,
     *         blocks: list<array{
     *             id: string,
     *             type: string,
     *             position: int,
     *             payload: array<string, mixed>
     *         }>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'unit_id' => $this->unitId,
            'course_status' => $this->courseStatus,
            'lessons' => $this->lessons,
        ];
    }
}
