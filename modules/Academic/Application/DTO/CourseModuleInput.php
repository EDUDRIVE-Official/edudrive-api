<?php

declare(strict_types=1);

namespace Modules\Academic\Application\DTO;

final readonly class CourseModuleInput
{
    /**
     * @param  list<string>  $prerequisiteModuleIds
     * @param  list<CourseUnitInput>  $units
     */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $description,
        public ?string $objectives,
        public ?int $durationMinutes,
        public int $position,
        public array $prerequisiteModuleIds,
        public array $units,
    ) {}
}
