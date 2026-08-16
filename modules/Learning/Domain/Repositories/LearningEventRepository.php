<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\Repositories;

use Modules\Learning\Domain\Entities\LearningEvent;

interface LearningEventRepository
{
    public function record(LearningEvent $event): void;

    /** @return list<LearningEvent> */
    public function findByEnrollmentId(string $enrollmentId): array;
}
