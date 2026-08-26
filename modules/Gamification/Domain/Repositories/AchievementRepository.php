<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

interface AchievementRepository
{
    public function save(Achievement $achievement): void;

    public function findById(AchievementId $id): ?Achievement;

    public function findByCode(AchievementCode $code): ?Achievement;

    /** @return list<Achievement> */
    public function all(): array;
}
