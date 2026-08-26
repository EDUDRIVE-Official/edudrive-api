<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetRoadPassportQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final readonly class GetRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(GetRoadPassportQuery $query): RoadPassportResponse
    {
        $passport = $this->passports->findById(RoadPassportId::fromString($query->roadPassportId));
        if ($passport === null) {
            throw RoadPassportNotFound::withId($query->roadPassportId);
        }

        if ($passport->userId() !== $query->userId && ! $query->canViewOthers) {
            throw RoadPassportNotFound::withId($query->roadPassportId);
        }

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
