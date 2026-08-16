<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CurriculumUnlockResponse
{
    /**
     * @param  list<array{module_id: string, completed: bool, unlocked: bool, units: list<array{unit_id: string, completed: bool, unlocked: bool}>}>  $modules
     */
    public function __construct(
        public string $enrollmentId,
        public string $courseId,
        public array $modules,
    ) {}

    /**
     * @return array{enrollment_id: string, course_id: string, modules: list<array{module_id: string, completed: bool, unlocked: bool, units: list<array{unit_id: string, completed: bool, unlocked: bool}>}>}
     */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'course_id' => $this->courseId,
            'modules' => $this->modules,
        ];
    }
}
