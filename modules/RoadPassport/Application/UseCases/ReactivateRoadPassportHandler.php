<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use DateTimeImmutable;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final readonly class ReactivateRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(ReactivateRoadPassportCommand $command): RoadPassportResponse
    {
        $passport = $this->passports->findById(RoadPassportId::fromString($command->roadPassportId));
        if ($passport === null) {
            throw RoadPassportNotFound::withId($command->roadPassportId);
        }

        $passport->reactivate(new DateTimeImmutable('now'));
        $this->passports->save($passport);

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
