<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Exceptions\ChallengeNotFound;
use Modules\Gamification\Application\Queries\GetChallengeQuery;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final readonly class GetChallengeHandler
{
    public function __construct(private ChallengeRepository $challenges) {}

    public function handle(GetChallengeQuery $query): ChallengeResponse
    {
        $challenge = $this->challenges->findById(ChallengeId::fromString($query->challengeId));
        if ($challenge === null) {
            throw ChallengeNotFound::withId($query->challengeId);
        }

        return ChallengeResponse::fromChallenge($challenge);
    }
}
