<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\ValueObjects;

final readonly class CompetencyExperience
{
    public function __construct(
        public string $competencyId,
        public int $totalPoints,
        public int $level,
    ) {}
}
