<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterAiModelCommand implements Command
{
    public function __construct(
        public string $name,
        public string $provider,
        public string $version,
        public ?string $ownerId,
        public ?string $useCase,
        public ?string $knownRisks,
    ) {}
}
