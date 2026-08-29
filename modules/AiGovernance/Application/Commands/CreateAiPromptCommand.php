<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateAiPromptCommand implements Command
{
    public function __construct(
        public string $identifier,
        public string $purpose,
        public ?string $modelId,
        public ?string $authorId,
        public string $content,
    ) {}
}
