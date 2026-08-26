<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\DecisionPoint;

interface DecisionPointRepository
{
    /** @param list<DecisionPoint> $points */
    public function saveBatch(array $points): void;

    /** @return list<DecisionPoint> */
    public function allForSession(string $sessionId): array;
}
