<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

interface AiSystemRepository
{
    public function save(AiSystem $system): void;

    public function findById(AiSystemId $id): ?AiSystem;

    /** @return list<AiSystem> */
    public function all(): array;
}
