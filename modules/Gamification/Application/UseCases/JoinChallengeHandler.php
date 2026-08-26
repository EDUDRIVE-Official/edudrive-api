<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\JoinChallengeCommand;
use Modules\Gamification\Application\Exceptions\ChallengeAlreadyJoined;
use Modules\Gamification\Application\Exceptions\ChallengeNotAvailable;
use Modules\Gamification\Application\Exceptions\ChallengeNotFound;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Enums\ChallengeStatus;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final readonly class JoinChallengeHandler
{
    public function __construct(
        private ChallengeRepository $challenges,
        private ChallengeParticipationRepository $participations,
    ) {}

    public function handle(JoinChallengeCommand $command): ChallengeParticipationResponse
    {
        $challenge = $this->challenges->findById(ChallengeId::fromString($command->challengeId));
        if ($challenge === null) {
            throw ChallengeNotFound::withId($command->challengeId);
        }

        $now = new DateTimeImmutable('now');

        if ($challenge->status() !== ChallengeStatus::Active || ! $challenge->isWithinWindow($now)) {
            throw ChallengeNotAvailable::create();
        }

        if ($this->participations->findByChallengeAndUser($command->challengeId, $command->userId) !== null) {
            throw ChallengeAlreadyJoined::create();
        }

        $participation = ChallengeParticipation::join(
            id: (string) Str::uuid(),
            challengeId: $command->challengeId,
            userId: $command->userId,
            joinedAt: $now,
        );

        $this->participations->save($participation);

        return ChallengeParticipationResponse::fromChallengeParticipation($participation);
    }
}
