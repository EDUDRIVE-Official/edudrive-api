<?php

declare(strict_types=1);

use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetRoadPassportByUserIdQuery;
use Modules\RoadPassport\Application\UseCases\GetRoadPassportByUserIdHandler;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class InMemoryRoadPassportRepositoryForGetByUserId implements RoadPassportRepository
{
    private ?RoadPassport $passport = null;

    public function withPassport(?RoadPassport $passport): void
    {
        $this->passport = $passport;
    }

    public function save(RoadPassport $passport): void
    {
        $this->passport = $passport;
    }

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        return $this->passport;
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        return $this->passport?->userId() === $userId ? $this->passport : null;
    }
}

it('devuelve el pasaporte vial de un usuario por su id', function (): void {
    $repository = new InMemoryRoadPassportRepositoryForGetByUserId;
    $repository->withPassport(RoadPassport::create(
        id: RoadPassportId::fromString('01981a64-8300-7b1d-b442-764ea7f92200'),
        userId: 'user-1',
    ));

    $handler = new GetRoadPassportByUserIdHandler($repository);
    $response = $handler->handle(new GetRoadPassportByUserIdQuery(userId: 'user-1'));

    expect($response->userId)->toBe('user-1')
        ->and($response->status)->toBe('active')
        ->and($response->level)->toBe(1);
});

it('rechaza consultar el pasaporte de un usuario que no tiene uno emitido', function (): void {
    $handler = new GetRoadPassportByUserIdHandler(new InMemoryRoadPassportRepositoryForGetByUserId);

    $handler->handle(new GetRoadPassportByUserIdQuery(userId: 'sin-pasaporte'));
})->throws(RoadPassportNotFound::class);
