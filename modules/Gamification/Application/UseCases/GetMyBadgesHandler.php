<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Responses\UserBadgeResponse;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;

final readonly class GetMyBadgesHandler
{
    public function __construct(private UserBadgeRepository $userBadges) {}

    /** @return list<UserBadgeResponse> */
    public function handle(GetMyBadgesQuery $query): array
    {
        return array_map(
            static fn (UserBadge $userBadge): UserBadgeResponse => UserBadgeResponse::fromUserBadge($userBadge),
            $this->userBadges->allForUser($query->userId),
        );
    }
}
