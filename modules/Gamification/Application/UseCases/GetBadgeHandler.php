<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Modules\Gamification\Application\Exceptions\BadgeNotFound;
use Modules\Gamification\Application\Queries\GetBadgeQuery;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class GetBadgeHandler
{
    public function __construct(private BadgeRepository $badges) {}

    public function handle(GetBadgeQuery $query): BadgeResponse
    {
        $badge = $this->badges->findById(BadgeId::fromString($query->badgeId));
        if ($badge === null) {
            throw BadgeNotFound::withId($query->badgeId);
        }

        return BadgeResponse::fromBadge($badge);
    }
}
