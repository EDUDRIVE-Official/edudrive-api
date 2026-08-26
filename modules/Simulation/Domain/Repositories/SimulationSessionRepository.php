<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

interface SimulationSessionRepository
{
    public function save(SimulationSession $session): void;

    public function findById(SimulationSessionId $id): ?SimulationSession;

    /** @return list<SimulationSession> */
    public function allForUser(string $userId): array;

    /** @return list<SimulationSession> */
    public function all(): array;
}
