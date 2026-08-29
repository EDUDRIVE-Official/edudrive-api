<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

interface AiProviderEvaluationRepository
{
    public function save(AiProviderEvaluation $evaluation): void;

    public function findById(AiProviderEvaluationId $id): ?AiProviderEvaluation;

    /** @return list<AiProviderEvaluation> */
    public function all(): array;
}
