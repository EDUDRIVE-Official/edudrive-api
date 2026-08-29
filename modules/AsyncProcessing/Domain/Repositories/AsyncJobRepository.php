<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Domain\Repositories;

use DateTimeImmutable;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

interface AsyncJobRepository
{
    public function save(AsyncJob $job): void;

    public function findById(AsyncJobId $id): ?AsyncJob;

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array;

    public function delete(AsyncJobId $id): void;
}
