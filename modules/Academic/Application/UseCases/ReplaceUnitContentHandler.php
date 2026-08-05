<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\DTO\ContentBlockInput;
use Modules\Academic\Application\DTO\LessonInput;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\CourseUnitNotFound;
use Modules\Academic\Application\Responses\UnitContentResponse;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\ReadModels\UnitContentSnapshot;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class ReplaceUnitContentHandler
{
    public function __construct(
        private CourseRepository $courses,
        private UnitContentRepository $contents,
    ) {}

    public function handle(ReplaceUnitContentCommand $command): UnitContentResponse
    {
        $courseId = CourseId::fromString($command->courseId);
        $unitId = CourseUnitId::fromString($command->unitId);
        $course = $this->courses->findById($courseId);

        if ($course === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        if (! $course->ownsUnit($unitId)) {
            throw CourseUnitNotFound::create();
        }

        $candidate = UnitContent::create(
            $unitId,
            array_map(
                static fn (LessonInput $lesson): Lesson => Lesson::create(
                    id: LessonId::fromString($lesson->id),
                    code: CurriculumCode::fromString($lesson->code),
                    title: $lesson->title,
                    summary: $lesson->summary,
                    durationMinutes: $lesson->durationMinutes,
                    position: $lesson->position,
                    blocks: array_map(
                        static fn (ContentBlockInput $block): ContentBlock => ContentBlockFactory::create(
                            id: ContentBlockId::fromString($block->id),
                            type: $block->type,
                            position: $block->position,
                            payload: $block->payload,
                        ),
                        $lesson->blocks,
                    ),
                ),
                $command->lessons,
            ),
        );

        $content = $this->contents->replaceAtomically($courseId, $unitId, $candidate);

        if ($content === null) {
            throw CourseNotFound::withId($command->courseId);
        }

        return UnitContentResponse::fromSnapshot(
            $courseId,
            new UnitContentSnapshot(CourseStatus::Draft, $content),
        );
    }
}
