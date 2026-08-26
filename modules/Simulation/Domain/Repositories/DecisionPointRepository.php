<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\DecisionPoint;

interface DecisionPointRepository
{
    /**
     * @param  list<DecisionPoint>  $points
     * @return int number of rows actually inserted (duplicates by id are ignored)
     */
    public function saveBatch(array $points): int;

    /** @return list<DecisionPoint> */
    public function allForSession(string $sessionId): array;
}
