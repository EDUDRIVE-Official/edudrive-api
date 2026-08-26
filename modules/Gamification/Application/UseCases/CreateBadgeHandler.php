<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CreateBadgeCommand;
use Modules\Gamification\Application\Exceptions\BadgeAlreadyExists;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class CreateBadgeHandler
{
    public function __construct(private BadgeRepository $badges) {}

    public function handle(CreateBadgeCommand $command): BadgeResponse
    {
        $code = BadgeCode::fromString($command->code);

        if ($this->badges->findByCode($code) !== null) {
            throw BadgeAlreadyExists::create();
        }

        $badge = Badge::create(
            id: BadgeId::fromString((string) Str::uuid()),
            code: $code,
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
