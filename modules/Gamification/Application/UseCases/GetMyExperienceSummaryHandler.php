<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\GetMyExperienceSummaryQuery;
use Modules\Gamification\Application\Responses\ExperienceSummaryResponse;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Services\ExperienceLevelCalculator;

final readonly class GetMyExperienceSummaryHandler
{
    public function __construct(private ExperienceEntryRepository $experienceEntries) {}

    public function handle(GetMyExperienceSummaryQuery $query): ExperienceSummaryResponse
    {
        $summary = (new ExperienceLevelCalculator)->summarize(
            $query->userId,
            $this->experienceEntries->allForUser($query->userId),
        );

        return ExperienceSummaryResponse::fromExperienceSummary($summary);
    }
}
