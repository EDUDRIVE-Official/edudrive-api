<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\TelemetryEvent;

interface TelemetryEventRepository
{
    /** @param list<TelemetryEvent> $events */
    public function saveBatch(array $events): void;

    /** @return list<TelemetryEvent> */
    public function allForSession(string $sessionId): array;
}
