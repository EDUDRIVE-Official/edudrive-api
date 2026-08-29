<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class RegisterAiSystemCommand implements Command
{
    /** @param list<string> $dataCategories */
    public function __construct(
        public string $name,
        public string $purpose,
        public string $functionalOwnerId,
        public ?string $technicalOwnerId,
        public string $riskLevel,
        public int $supervisionLevel,
        public array $dataCategories,
        public ?string $providerEvaluationId,
    ) {}
}
