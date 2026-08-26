<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\GrantBadgeCommand;
use Modules\Gamification\Application\Exceptions\BadgeAlreadyGranted;
use Modules\Gamification\Application\Exceptions\BadgeNotAvailable;
use Modules\Gamification\Application\Exceptions\BadgeNotFound;
use Modules\Gamification\Application\Responses\UserBadgeResponse;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Enums\BadgeStatus;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class GrantBadgeHandler
{
    public function __construct(
        private BadgeRepository $badges,
        private UserBadgeRepository $userBadges,
    ) {}

    public function handle(GrantBadgeCommand $command): UserBadgeResponse
    {
        $badge = $this->badges->findById(BadgeId::fromString($command->badgeId));
        if ($badge === null) {
            throw BadgeNotFound::withId($command->badgeId);
        }

        if ($badge->status() !== BadgeStatus::Active) {
            throw BadgeNotAvailable::create();
        }

        if ($this->userBadges->findByBadgeAndUser($command->badgeId, $command->userId) !== null) {
            throw BadgeAlreadyGranted::create();
        }

        $userBadge = UserBadge::grant(
            id: (string) Str::uuid(),
            badgeId: $command->badgeId,
            userId: $command->userId,
            awardedVersion: $badge->version(),
            evidence: $command->evidence,
            earnedAt: new DateTimeImmutable('now'),
        );

        $this->userBadges->save($userBadge);

        return UserBadgeResponse::fromUserBadge($userBadge);
    }
}
