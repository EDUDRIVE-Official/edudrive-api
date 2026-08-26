<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use DateTimeImmutable;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final readonly class RevokeRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(RevokeRoadPassportCommand $command): RoadPassportResponse
    {
        $passport = $this->passports->findById(RoadPassportId::fromString($command->roadPassportId));
        if ($passport === null) {
            throw RoadPassportNotFound::withId($command->roadPassportId);
        }

        $passport->revoke($command->reason, new DateTimeImmutable('now'));
        $this->passports->save($passport);

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
