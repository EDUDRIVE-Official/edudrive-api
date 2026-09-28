<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetRoadPassportByUserIdQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final readonly class GetRoadPassportByUserIdHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(GetRoadPassportByUserIdQuery $query): RoadPassportResponse
    {
        $passport = $this->passports->findByUserId($query->userId);
        if ($passport === null) {
            throw RoadPassportNotFound::forUser();
        }

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
