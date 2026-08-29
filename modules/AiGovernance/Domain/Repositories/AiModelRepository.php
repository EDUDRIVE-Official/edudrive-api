<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Aggregates\AiModel;
use Modules\AiGovernance\Domain\ValueObjects\AiModelId;

interface AiModelRepository
{
    public function save(AiModel $model): void;

    public function findById(AiModelId $id): ?AiModel;

    /** @return list<AiModel> */
    public function all(): array;
}
