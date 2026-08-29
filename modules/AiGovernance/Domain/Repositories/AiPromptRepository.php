<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

interface AiPromptRepository
{
    public function save(AiPrompt $prompt): void;

    public function findById(AiPromptId $id): ?AiPrompt;

    /** @return list<AiPrompt> */
    public function all(): array;
}
