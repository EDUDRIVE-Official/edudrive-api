<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Responses;

use Modules\Gamification\Domain\ValueObjects\CompetencyExperience;
use Modules\Gamification\Domain\ValueObjects\ExperienceSummary;

final readonly class ExperienceSummaryResponse
{
    /** @param list<CompetencyExperience> $competencies */
    public function __construct(
        public string $userId,
        public int $totalPoints,
        public int $generalLevel,
        public array $competencies,
    ) {}

    public static function fromExperienceSummary(ExperienceSummary $summary): self
    {
        return new self(
            userId: $summary->userId,
            totalPoints: $summary->totalPoints,
            generalLevel: $summary->generalLevel,
            competencies: $summary->competencies,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'total_points' => $this->totalPoints,
            'general_level' => $this->generalLevel,
            'competencies' => array_map(
                static fn (CompetencyExperience $competency): array => [
                    'competency_id' => $competency->competencyId,
                    'total_points' => $competency->totalPoints,
                    'level' => $competency->level,
                ],
                $this->competencies,
            ),
        ];
    }
}
