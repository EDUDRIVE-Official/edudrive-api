<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Modules\Gamification\Application\Commands\RetireChallengeCommand;
use Modules\Gamification\Application\Exceptions\ChallengeNotFound;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final readonly class RetireChallengeHandler
{
    public function __construct(private ChallengeRepository $challenges) {}

    public function handle(RetireChallengeCommand $command): ChallengeResponse
    {
        $challenge = $this->challenges->findById(ChallengeId::fromString($command->challengeId));
        if ($challenge === null) {
            throw ChallengeNotFound::withId($command->challengeId);
        }

        $challenge->retire($command->reason, new DateTimeImmutable('now'));
        $this->challenges->save($challenge);

        return ChallengeResponse::fromChallenge($challenge);
    }
}
