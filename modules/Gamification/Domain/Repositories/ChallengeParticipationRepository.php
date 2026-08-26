<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Repositories;

use Modules\Gamification\Domain\Entities\ChallengeParticipation;

interface ChallengeParticipationRepository
{
    public function save(ChallengeParticipation $participation): void;

    public function findByChallengeAndUser(string $challengeId, string $userId): ?ChallengeParticipation;

    /** @return list<ChallengeParticipation> */
    public function allForUser(string $userId): array;
}
