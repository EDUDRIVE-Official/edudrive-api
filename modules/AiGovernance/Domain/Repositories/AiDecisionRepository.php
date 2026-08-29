<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

interface AiDecisionRepository
{
    public function save(AiDecision $decision): void;

    public function findById(string $id): ?AiDecision;

    /** @return list<AiDecision> */
    public function findByAiSystem(AiSystemId $aiSystemId): array;
}
