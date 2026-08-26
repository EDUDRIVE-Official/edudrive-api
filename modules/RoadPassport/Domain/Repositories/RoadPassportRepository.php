<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Repositories;

use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

interface RoadPassportRepository
{
    public function save(RoadPassport $passport): void;

    public function findById(RoadPassportId $id): ?RoadPassport;

    public function findByUserId(string $userId): ?RoadPassport;
}
