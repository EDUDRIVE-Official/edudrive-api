<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Entities\ExperienceEntry;

interface ExperienceEntryRepository
{
    public function save(ExperienceEntry $entry): void;

    /** @return list<ExperienceEntry> */
    public function allForUser(string $userId): array;
}
