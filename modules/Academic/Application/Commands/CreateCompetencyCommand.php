<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateCompetencyCommand implements Command
{
    public function __construct(
        public string $code,
        public string $title,
        public string $description,
        public string $category,
        public string $masteryLevel,
    ) {}
}
