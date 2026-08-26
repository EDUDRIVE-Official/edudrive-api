<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CreateChallengeCommand;
use Modules\Gamification\Application\Exceptions\ChallengeAlreadyExists;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final readonly class CreateChallengeHandler
{
    public function __construct(private ChallengeRepository $challenges) {}

    public function handle(CreateChallengeCommand $command): ChallengeResponse
    {
        $code = ChallengeCode::fromString($command->code);

        if ($this->challenges->findByCode($code) !== null) {
            throw ChallengeAlreadyExists::create();
        }

        $challenge = Challenge::create(
            id: ChallengeId::fromString((string) Str::uuid()),
            code: $code,
            name: $command->name,
            description: $command->description,
            type: ChallengeType::from($command->type),
            reward: $command->reward,
            startsAt: $command->startsAt,
            endsAt: $command->endsAt,
        );

        $this->challenges->save($challenge);

        return ChallengeResponse::fromChallenge($challenge);
    }
}
