<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\RecordExperienceCommand;
use Modules\Gamification\Application\Responses\ExperienceEntryResponse;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;

final readonly class RecordExperienceHandler
{
    public function __construct(private ExperienceEntryRepository $experienceEntries) {}

    public function handle(RecordExperienceCommand $command): ExperienceEntryResponse
    {
        $entry = ExperienceEntry::record(
            id: (string) Str::uuid(),
            userId: $command->userId,
            points: $command->points,
            competencyId: $command->competencyId,
            reason: $command->reason,
            recordedAt: new DateTimeImmutable('now'),
        );

        $this->experienceEntries->save($entry);

        return ExperienceEntryResponse::fromExperienceEntry($entry);
    }
}
