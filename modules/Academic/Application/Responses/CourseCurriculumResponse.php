<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;

final readonly class CourseCurriculumResponse
{
    /**
     * @param list<array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     description: string,
     *     objectives: string|null,
     *     duration_minutes: int|null,
     *     position: int,
     *     prerequisite_module_ids: list<string>,
     *     units: list<array{
     *         id: string,
     *         code: string,
     *         title: string,
     *         description: string,
     *         objectives: string|null,
     *         duration_minutes: int|null,
     *         position: int,
     *         prerequisite_unit_ids: list<string>
     *     }>
     * }> $modules
     */
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $status,
        private array $modules,
    ) {}

    public static function fromCourse(Course $course): self
    {
        return new self(
            id: $course->id()->value(),
            code: $course->code()->value(),
            title: $course->title()->value(),
            status: $course->status()->value,
            modules: array_map(
                static fn (CourseModule $module): array => [
                    'id' => $module->id()->value(),
                    'code' => $module->code()->value(),
                    'title' => $module->title(),
                    'description' => $module->description(),
                    'objectives' => $module->objectives(),
                    'duration_minutes' => $module->durationMinutes(),
                    'position' => $module->position(),
                    'prerequisite_module_ids' => array_map(
                        static fn (CourseModuleId $id): string => $id->value(),
                        $module->prerequisiteModuleIds(),
                    ),
                    'units' => array_map(
                        static fn (CourseUnit $unit): array => [
                            'id' => $unit->id()->value(),
                            'code' => $unit->code()->value(),
                            'title' => $unit->title(),
                            'description' => $unit->description(),
                            'objectives' => $unit->objectives(),
                            'duration_minutes' => $unit->durationMinutes(),
                            'position' => $unit->position(),
                            'prerequisite_unit_ids' => array_map(
                                static fn (CourseUnitId $id): string => $id->value(),
                                $unit->prerequisiteUnitIds(),
                            ),
                        ],
                        $module->units(),
                    ),
                ],
                $course->modules(),
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     status: string,
     *     modules: list<array{
     *         id: string,
     *         code: string,
     *         title: string,
     *         description: string,
     *         objectives: string|null,
     *         duration_minutes: int|null,
     *         position: int,
     *         prerequisite_module_ids: list<string>,
     *         units: list<array{
     *             id: string,
     *             code: string,
     *             title: string,
     *             description: string,
     *             objectives: string|null,
     *             duration_minutes: int|null,
     *             position: int,
     *             prerequisite_unit_ids: list<string>
     *         }>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'status' => $this->status,
            'modules' => $this->modules,
        ];
    }
}
