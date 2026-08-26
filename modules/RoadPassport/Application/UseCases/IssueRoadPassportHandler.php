<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\UseCases;

use Illuminate\Support\Str;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportAlreadyExists;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final readonly class IssueRoadPassportHandler
{
    public function __construct(private RoadPassportRepository $passports) {}

    public function handle(IssueRoadPassportCommand $command): RoadPassportResponse
    {
        if ($this->passports->findByUserId($command->userId) !== null) {
            throw RoadPassportAlreadyExists::create();
        }

        $passport = RoadPassport::create(
            id: RoadPassportId::fromString((string) Str::uuid()),
            userId: $command->userId,
        );

        $this->passports->save($passport);

        return RoadPassportResponse::fromRoadPassport($passport);
    }
}
