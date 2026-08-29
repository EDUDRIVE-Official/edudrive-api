<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RetireAiPromptCommand implements Command
{
    public function __construct(
        public string $promptId,
    ) {}
}
