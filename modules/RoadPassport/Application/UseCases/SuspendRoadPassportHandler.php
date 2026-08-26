<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use DateTimeImmutable;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final readonly class SuspendRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(SuspendRoadPassportCommand $command): RoadPassportResponse
    {
        $passport = $this->passports->findById(RoadPassportId::fromString($command->roadPassportId));
        if ($passport === null) {
            throw RoadPassportNotFound::withId($command->roadPassportId);
        }

        $passport->suspend($command->reason, new DateTimeImmutable('now'));
        $this->passports->save($passport);

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
