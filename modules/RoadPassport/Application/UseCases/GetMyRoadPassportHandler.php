<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final readonly class GetMyRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(GetMyRoadPassportQuery $query): RoadPassportResponse
    {
        $passport = $this->passports->findByUserId($query->userId);
        if ($passport === null) {
            throw RoadPassportNotFound::forUser();
        }

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
