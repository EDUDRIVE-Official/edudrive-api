<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\TelemetryEvent;

interface TelemetryEventRepository
{
    /**
     * @param  list<TelemetryEvent>  $events
     * @return int number of rows actually inserted (duplicates by id are ignored)
     */
    public function saveBatch(array $events): int;

    /** @return list<TelemetryEvent> */
    public function allForSession(string $sessionId): array;
}
