<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Repositories\BadgeRepository;

final readonly class ListBadgesHandler
{
    public function __construct(private BadgeRepository $badges) {}

    /** @return list<BadgeResponse> */
    public function handle(ListBadgesQuery $query): array
    {
        return array_map(
            static fn (Badge $badge): BadgeResponse => BadgeResponse::fromBadge($badge),
            $this->badges->all(),
        );
    }
}
