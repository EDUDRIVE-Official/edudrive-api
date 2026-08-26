<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Exceptions\AchievementAlreadyExists;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final readonly class CreateAchievementHandler
{
    public function __construct(private AchievementRepository $achievements) {}

    public function handle(CreateAchievementCommand $command): AchievementResponse
    {
        $code = AchievementCode::fromString($command->code);

        if ($this->achievements->findByCode($code) !== null) {
            throw AchievementAlreadyExists::create();
        }

        $achievement = Achievement::create(
            id: AchievementId::fromString((string) Str::uuid()),
            code: $code,
            name: $command->name,
            description: $command->description,
            earningRule: $command->earningRule,
        );

        $this->achievements->save($achievement);

        return AchievementResponse::fromAchievement($achievement);
    }
}
