<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

interface ChallengeRepository
{
    public function save(Challenge $challenge): void;

    public function findById(ChallengeId $id): ?Challenge;

    public function findByCode(ChallengeCode $code): ?Challenge;

    /** @return list<Challenge> */
    public function all(): array;
}
