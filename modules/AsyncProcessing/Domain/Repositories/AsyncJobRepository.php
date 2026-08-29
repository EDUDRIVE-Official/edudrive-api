<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Domain\Repositories;

use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

interface AsyncJobRepository
{
    public function save(AsyncJob $job): void;

    public function findById(AsyncJobId $id): ?AsyncJob;
}
