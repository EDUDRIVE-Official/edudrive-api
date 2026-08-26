<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Commands\UpdateBadgeCommand;
use Modules\Gamification\Application\Exceptions\BadgeNotFound;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class UpdateBadgeHandler
{
    public function __construct(private BadgeRepository $badges) {}

    public function handle(UpdateBadgeCommand $command): BadgeResponse
    {
        $badge = $this->badges->findById(BadgeId::fromString($command->badgeId));
        if ($badge === null) {
            throw BadgeNotFound::withId($command->badgeId);
        }

        $badge->updateContent(
            name: $command->name,
            description: $command->description,
            criteria: $command->criteria,
            category: BadgeCategory::from($command->category),
            level: BadgeLevel::from($command->level),
        );

        $this->badges->save($badge);

        return BadgeResponse::fromBadge($badge);
    }
}
