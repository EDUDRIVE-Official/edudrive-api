<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\GetMyChallengeParticipationsQuery;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;

final readonly class GetMyChallengeParticipationsHandler
{
    public function __construct(private ChallengeParticipationRepository $participations) {}

    /** @return list<ChallengeParticipationResponse> */
    public function handle(GetMyChallengeParticipationsQuery $query): array
    {
        return array_map(
            static fn (ChallengeParticipation $participation): ChallengeParticipationResponse => ChallengeParticipationResponse::fromChallengeParticipation($participation),
            $this->participations->allForUser($query->userId),
        );
    }
}
