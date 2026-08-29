<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class InvokeAiGatewayCommand implements Command
{
    public function __construct(
        public string $aiSystemId,
        public ?string $requestedByUserId,
        public ?string $promptId,
        public string $input,
    ) {}
}
