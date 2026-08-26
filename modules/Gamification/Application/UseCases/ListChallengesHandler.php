<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\ListChallengesQuery;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;

final readonly class ListChallengesHandler
{
    public function __construct(private ChallengeRepository $challenges) {}

    /** @return list<ChallengeResponse> */
    public function handle(ListChallengesQuery $query): array
    {
        return array_map(
            static fn (Challenge $challenge): ChallengeResponse => ChallengeResponse::fromChallenge($challenge),
            $this->challenges->all(),
        );
    }
}
