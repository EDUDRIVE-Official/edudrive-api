<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Commands;

use DateTimeImmutable;
use Modules\Foundation\Application\Commands\Command;

final readonly class CreateChallengeCommand implements Command
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        public string $type,
        public string $reward,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
    ) {}
}
