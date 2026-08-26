<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\ValueObjects;

final readonly class ExperienceSummary
{
    /** @param list<CompetencyExperience> $competencies */
    public function __construct(
        public string $userId,
        public int $totalPoints,
        public int $generalLevel,
        public array $competencies,
    ) {}
}
