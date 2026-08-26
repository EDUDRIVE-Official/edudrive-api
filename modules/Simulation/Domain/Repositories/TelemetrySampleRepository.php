<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Entities\TelemetrySample;

interface TelemetrySampleRepository
{
    /**
     * @param  list<TelemetrySample>  $samples
     * @return int number of rows actually inserted (duplicates by id are ignored)
     */
    public function saveBatch(array $samples): int;

    /** @return list<TelemetrySample> */
    public function allForSession(string $sessionId): array;
}
