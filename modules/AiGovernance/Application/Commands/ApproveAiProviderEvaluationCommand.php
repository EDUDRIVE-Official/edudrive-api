<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ApproveAiProviderEvaluationCommand implements Command
{
    public function __construct(
        public string $providerEvaluationId,
        public ?string $nextReviewDueAt,
    ) {}
}
