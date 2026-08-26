<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use DateTimeImmutable;
use Modules\Gamification\Application\Commands\RetireBadgeCommand;
use Modules\Gamification\Application\Exceptions\BadgeNotFound;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class RetireBadgeHandler
{
    public function __construct(private BadgeRepository $badges) {}

    public function handle(RetireBadgeCommand $command): BadgeResponse
    {
        $badge = $this->badges->findById(BadgeId::fromString($command->badgeId));
        if ($badge === null) {
            throw BadgeNotFound::withId($command->badgeId);
        }

        $badge->retire($command->reason, new DateTimeImmutable('now'));
        $this->badges->save($badge);

        return BadgeResponse::fromBadge($badge);
    }
}
