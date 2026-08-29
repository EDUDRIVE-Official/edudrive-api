<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ApproveAiDecisionCommand implements Command
{
    public function __construct(
        public string $decisionId,
        public string $reviewerUserId,
    ) {}
}
