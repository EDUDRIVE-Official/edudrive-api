<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Repositories;

use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

interface AiIncidentRepository
{
    public function save(AiIncident $incident): void;

    public function findById(string $id): ?AiIncident;

    /** @return list<AiIncident> */
    public function findByAiSystem(AiSystemId $aiSystemId): array;
}
