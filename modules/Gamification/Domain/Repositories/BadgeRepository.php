<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

interface BadgeRepository
{
    public function save(Badge $badge): void;

    public function findById(BadgeId $id): ?Badge;

    public function findByCode(BadgeCode $code): ?Badge;

    /** @return list<Badge> */
    public function all(): array;
}
