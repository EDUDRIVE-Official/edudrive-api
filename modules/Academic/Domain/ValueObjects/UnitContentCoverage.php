<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

final readonly class UnitContentCoverage
{
    /** @param array<string, CourseUnitId> $unitIds */
    private function __construct(private array $unitIds) {}

    /** @param list<CourseUnitId> $unitIds */
    public static function fromUnitIds(array $unitIds): self
    {
        $deduplicated = [];

        foreach ($unitIds as $unitId) {
            $deduplicated[$unitId->value()] = $unitId;
        }

        return new self($deduplicated);
    }

    public function covers(CourseUnitId $unitId): bool
    {
        return isset($this->unitIds[$unitId->value()]);
    }

    /** @return list<CourseUnitId> */
    public function unitIds(): array
    {
        return array_values($this->unitIds);
    }
}
