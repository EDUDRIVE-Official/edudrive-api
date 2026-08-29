<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Queries;

use Modules\Foundation\Application\Queries\Query;

final readonly class GetAiIncidentQuery implements Query
{
    public function __construct(
        public string $incidentId,
    ) {}
}
