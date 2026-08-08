<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidLessonPosition;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;

final class UnitContent
{
    /** @var list<Lesson> */
    private array $lessons;

    /**
     * @param  list<Lesson>  $lessons
     */
    private function __construct(
        private readonly CourseUnitId $unitId,
        array $lessons,
    ) {
        $this->lessons = $lessons;
    }

    /**
     * @param  list<Lesson>  $lessons
     */
    public static function create(CourseUnitId $unitId, array $lessons): self
    {
        self::validateLessons($lessons);

        return new self($unitId, $lessons);
    }

    public function unitId(): CourseUnitId
    {
        return $this->unitId;
    }

    /** @return list<Lesson> */
    public function lessons(): array
    {
        return $this->lessons;
    }

    /**
     * @param  list<Lesson>  $lessons
     */
    public function replaceLessons(array $lessons): void
    {
        self::validateLessons($lessons);

        $this->lessons = $lessons;
    }

    public function isComplete(): bool
    {
        if ($this->lessons === []) {
            return false;
        }

        foreach ($this->lessons as $lesson) {
            if ($lesson->blocks() === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<Lesson>  $lessons
     */
    private static function validateLessons(array $lessons): void
    {
        $lessonIds = [];
        $lessonCodes = [];
        $blockIds = [];

        foreach ($lessons as $index => $lesson) {
            if ($lesson->position() !== $index + 1) {
                throw InvalidLessonPosition::create();
            }

            $lessonId = $lesson->id()->value();
            $lessonCode = strtoupper($lesson->code()->value());

            if (isset($lessonIds[$lessonId]) || isset($lessonCodes[$lessonCode])) {
                throw InvalidContentBlock::create();
            }

            $lessonIds[$lessonId] = true;
            $lessonCodes[$lessonCode] = true;

            foreach ($lesson->blocks() as $block) {
                self::registerBlockId($block, $blockIds);
            }
        }
    }

    /**
     * @param  array<string, true>  $blockIds
     */
    private static function registerBlockId(ContentBlock $block, array &$blockIds): void
    {
        $blockId = $block->id()->value();

        if (isset($blockIds[$blockId])) {
            throw InvalidContentBlock::create();
        }

        $blockIds[$blockId] = true;
    }
}
