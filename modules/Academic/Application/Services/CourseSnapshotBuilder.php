<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\UnitContentRepository;

final readonly class CourseSnapshotBuilder
{
    public function __construct(
        private UnitContentRepository $contents,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Course $course): array
    {
        $modules = [];

        foreach ($course->modules() as $module) {
            $modules[] = $this->serializeModule($course, $module);
        }

        return [
            'course' => [
                'id' => $course->id()->value(),
                'code' => $course->code()->value(),
                'title' => $course->title()->value(),
                'description' => $course->description(),
                'objectives' => $course->objectives(),
                'prerequisites' => $course->prerequisites(),
                'modality' => $course->modality()?->value,
                'duration_hours' => $course->durationHours(),
                'published_at' => $course->publishedAt()?->format(DATE_ATOM),
            ],
            'modules' => $modules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeModule(Course $course, CourseModule $module): array
    {
        $units = [];

        foreach ($module->units() as $unit) {
            $units[] = $this->serializeUnit($course, $unit);
        }

        return [
            'id' => $module->id()->value(),
            'code' => $module->code()->value(),
            'title' => $module->title(),
            'description' => $module->description(),
            'objectives' => $module->objectives(),
            'duration_minutes' => $module->durationMinutes(),
            'position' => $module->position(),
            'prerequisite_module_ids' => array_map(
                static fn ($prerequisite): string => $prerequisite->value(),
                $module->prerequisiteModuleIds(),
            ),
            'units' => $units,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUnit(Course $course, CourseUnit $unit): array
    {
        $content = $this->contents->findForCourseUnit($course->id(), $unit->id());

        $lessons = [];

        if ($content !== null) {
            foreach ($content->lessons() as $lesson) {
                $lessons[] = $this->serializeLesson($lesson);
            }
        }

        return [
            'id' => $unit->id()->value(),
            'code' => $unit->code()->value(),
            'title' => $unit->title(),
            'description' => $unit->description(),
            'objectives' => $unit->objectives(),
            'duration_minutes' => $unit->durationMinutes(),
            'position' => $unit->position(),
            'prerequisite_unit_ids' => array_map(
                static fn ($prerequisite): string => $prerequisite->value(),
                $unit->prerequisiteUnitIds(),
            ),
            'lessons' => $lessons,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLesson(Lesson $lesson): array
    {
        $blocks = [];

        foreach ($lesson->blocks() as $block) {
            $blocks[] = [
                'id' => $block->id()->value(),
                'type' => $block->type()->value,
                'position' => $block->position(),
                'payload' => $block->payload(),
            ];
        }

        return [
            'id' => $lesson->id()->value(),
            'code' => $lesson->code()->value(),
            'title' => $lesson->title(),
            'summary' => $lesson->summary(),
            'duration_minutes' => $lesson->durationMinutes(),
            'position' => $lesson->position(),
            'blocks' => $blocks,
        ];
    }
}
