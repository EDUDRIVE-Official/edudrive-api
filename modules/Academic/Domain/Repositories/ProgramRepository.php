<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Repositories;

use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

interface ProgramRepository
{
    public function save(EducationalProgram $program): void;

    public function findById(ProgramId $id): ?EducationalProgram;

    public function findByCode(ProgramCode $code): ?EducationalProgram;

    public function existsByCode(ProgramCode $code): bool;

    /** @return list<EducationalProgram> */
    public function all(): array;
}
