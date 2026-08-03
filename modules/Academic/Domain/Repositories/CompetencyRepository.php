<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\Competency;
use Modules\Academic\Domain\ValueObjects\CompetencyCode;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

interface CompetencyRepository
{
    public function save(Competency $competency): void;

    public function findById(CompetencyId $id): ?Competency;

    public function findByCode(CompetencyCode $code): ?Competency;

    public function existsByCode(CompetencyCode $code): bool;

    /** @return list<Competency> */
    public function all(): array;
}
