<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class ModuleUnlockStatus
{
    /** @param list<UnitUnlockStatus> $units */
    public function __construct(
        public CourseModuleId $moduleId,
        public bool $completed,
        public bool $unlocked,
        public array $units,
    ) {}
}
