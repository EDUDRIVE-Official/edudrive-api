<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\TelemetrySample;

interface TelemetrySampleRepository
{
    /** @param list<TelemetrySample> $samples */
    public function saveBatch(array $samples): void;

    /** @return list<TelemetrySample> */
    public function allForSession(string $sessionId): array;
}
