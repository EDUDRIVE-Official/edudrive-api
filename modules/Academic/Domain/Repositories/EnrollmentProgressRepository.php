<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\EnrollmentProgress;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;

interface EnrollmentProgressRepository
{
    public function save(EnrollmentProgress $progress): void;

    public function findByEnrollmentId(EnrollmentId $enrollmentId): EnrollmentProgress;
}
