<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Services;

use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\ValueObjects\CompetencyExperience;
use Modules\Gamification\Domain\ValueObjects\ExperienceSummary;

final class ExperienceLevelCalculator
{
    private const int POINTS_PER_LEVEL = 100;

    /** @param list<ExperienceEntry> $entries */
    public function summarize(string $userId, array $entries): ExperienceSummary
    {
        $totalPoints = 0;
        $pointsByCompetency = [];

        foreach ($entries as $entry) {
            $totalPoints += $entry->points();

            if ($entry->competencyId() !== null) {
                $pointsByCompetency[$entry->competencyId()] = ($pointsByCompetency[$entry->competencyId()] ?? 0) + $entry->points();
            }
        }

        $competencies = [];
        foreach ($pointsByCompetency as $competencyId => $points) {
            $competencies[] = new CompetencyExperience(
                competencyId: $competencyId,
                totalPoints: $points,
                level: self::levelForPoints($points),
            );
        }

        return new ExperienceSummary(
            userId: $userId,
            totalPoints: $totalPoints,
            generalLevel: self::levelForPoints($totalPoints),
            competencies: $competencies,
        );
    }

    private static function levelForPoints(int $points): int
    {
        return intdiv($points, self::POINTS_PER_LEVEL) + 1;
    }
}
