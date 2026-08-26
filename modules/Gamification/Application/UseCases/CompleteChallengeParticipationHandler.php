<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Modules\Gamification\Application\Commands\CompleteChallengeParticipationCommand;
use Modules\Gamification\Application\Exceptions\ChallengeParticipationNotFound;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;

final readonly class CompleteChallengeParticipationHandler
{
    public function __construct(private ChallengeParticipationRepository $participations) {}

    public function handle(CompleteChallengeParticipationCommand $command): ChallengeParticipationResponse
    {
        $participation = $this->participations->findByChallengeAndUser($command->challengeId, $command->userId);
        if ($participation === null) {
            throw ChallengeParticipationNotFound::create();
        }

        $participation->complete($command->evidence, new DateTimeImmutable('now'));
        $this->participations->save($participation);

        return ChallengeParticipationResponse::fromChallengeParticipation($participation);
    }
}
