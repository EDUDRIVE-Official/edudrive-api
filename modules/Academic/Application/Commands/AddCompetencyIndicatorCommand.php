<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class AddCompetencyIndicatorCommand implements Command
{
    public function __construct(
        public string $competencyId,
        public string $subcompetencyCode,
        public string $code,
        public string $description,
    ) {}
}
