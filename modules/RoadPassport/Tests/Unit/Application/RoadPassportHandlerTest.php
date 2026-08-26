<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\RoadPassport\Application\Commands\ChangeRoadPassportLevelCommand;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportAlreadyExists;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetMyRoadPassportQuery;
use Modules\RoadPassport\Application\Queries\GetRoadPassportQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Application\UseCases\ChangeRoadPassportLevelHandler;
use Modules\RoadPassport\Application\UseCases\GetMyRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\GetRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\IssueRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\ReactivateRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\RevokeRoadPassportHandler;
use Modules\RoadPassport\Application\UseCases\SuspendRoadPassportHandler;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Exceptions\InvalidRoadPassportTransition;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

final class InMemoryRoadPassportRepository implements RoadPassportRepository
{
    /** @var array<string, RoadPassport> */
    public array $items = [];

    public function save(RoadPassport $passport): void
    {
        $this->items[$passport->id()->value()] = $passport;
    }

    public function findById(RoadPassportId $id): ?RoadPassport
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByUserId(string $userId): ?RoadPassport
    {
        foreach ($this->items as $passport) {
            if ($passport->userId() === $userId) {
                return $passport;
            }
        }

        return null;
    }
}

function persistedRoadPassportFor(InMemoryRoadPassportRepository $repository, ?string $userId = null): RoadPassport
{
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $userId ?? (string) Str::uuid(),
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
    $repository->save($passport);

    return $passport;
}

it('emite un pasaporte vial nuevo en nivel 1', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $userId = (string) Str::uuid();

    $response = (new IssueRoadPassportHandler($repository))->handle(new IssueRoadPassportCommand($userId));

    expect($response)->toBeInstanceOf(RoadPassportResponse::class)
        ->and($response->userId)->toBe($userId)
        ->and($response->status)->toBe('active')
        ->and($response->level)->toBe(1);
});

it('rechaza emitir un segundo pasaporte para el mismo usuario', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $userId = (string) Str::uuid();
    persistedRoadPassportFor($repository, $userId);

    expect(fn () => (new IssueRoadPassportHandler($repository))->handle(new IssueRoadPassportCommand($userId)))
        ->toThrow(RoadPassportAlreadyExists::class);
});

it('suspende, reactiva, revoca y sube de nivel un pasaporte existente', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $passport = persistedRoadPassportFor($repository);
    $id = $passport->id()->value();

    $suspended = (new SuspendRoadPassportHandler($repository))->handle(new SuspendRoadPassportCommand($id, 'Motivo'));
    expect($suspended->status)->toBe('suspended');

    $reactivated = (new ReactivateRoadPassportHandler($repository))->handle(new ReactivateRoadPassportCommand($id));
    expect($reactivated->status)->toBe('active');

    $leveled = (new ChangeRoadPassportLevelHandler($repository))->handle(new ChangeRoadPassportLevelCommand($id, 4));
    expect($leveled->level)->toBe(4);

    $revoked = (new RevokeRoadPassportHandler($repository))->handle(new RevokeRoadPassportCommand($id, 'Fraude'));
    expect($revoked->status)->toBe('revoked');
});

it('rechaza operar sobre un pasaporte inexistente', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $missingId = (string) Str::uuid();

    expect(fn () => (new SuspendRoadPassportHandler($repository))->handle(new SuspendRoadPassportCommand($missingId)))
        ->toThrow(RoadPassportNotFound::class)
        ->and(fn () => (new ReactivateRoadPassportHandler($repository))->handle(new ReactivateRoadPassportCommand($missingId)))
        ->toThrow(RoadPassportNotFound::class)
        ->and(fn () => (new RevokeRoadPassportHandler($repository))->handle(new RevokeRoadPassportCommand($missingId)))
        ->toThrow(RoadPassportNotFound::class)
        ->and(fn () => (new ChangeRoadPassportLevelHandler($repository))->handle(new ChangeRoadPassportLevelCommand($missingId, 2)))
        ->toThrow(RoadPassportNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $passport = persistedRoadPassportFor($repository);

    expect(fn () => (new ReactivateRoadPassportHandler($repository))->handle(new ReactivateRoadPassportCommand($passport->id()->value())))
        ->toThrow(InvalidRoadPassportTransition::class);
});

it('devuelve el pasaporte al dueno o a un tercero con permiso ampliado', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $passport = persistedRoadPassportFor($repository);

    $ownResponse = (new GetRoadPassportHandler($repository))->handle(new GetRoadPassportQuery(
        roadPassportId: $passport->id()->value(),
        userId: $passport->userId(),
        canViewOthers: false,
    ));
    expect($ownResponse->id)->toBe($passport->id()->value());

    $othersResponse = (new GetRoadPassportHandler($repository))->handle(new GetRoadPassportQuery(
        roadPassportId: $passport->id()->value(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    ));
    expect($othersResponse->id)->toBe($passport->id()->value());
});

it('rechaza consultar el pasaporte de un tercero sin permiso ampliado', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $passport = persistedRoadPassportFor($repository);

    expect(fn () => (new GetRoadPassportHandler($repository))->handle(new GetRoadPassportQuery(
        roadPassportId: $passport->id()->value(),
        userId: (string) Str::uuid(),
        canViewOthers: false,
    )))->toThrow(RoadPassportNotFound::class);
});

it('rechaza consultar un pasaporte inexistente', function (): void {
    $repository = new InMemoryRoadPassportRepository;

    expect(fn () => (new GetRoadPassportHandler($repository))->handle(new GetRoadPassportQuery(
        roadPassportId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        canViewOthers: true,
    )))->toThrow(RoadPassportNotFound::class);
});

it('devuelve el propio pasaporte por el id del usuario autenticado', function (): void {
    $repository = new InMemoryRoadPassportRepository;
    $passport = persistedRoadPassportFor($repository);

    $response = (new GetMyRoadPassportHandler($repository))->handle(new GetMyRoadPassportQuery($passport->userId()));

    expect($response->id)->toBe($passport->id()->value());
});

it('rechaza consultar el propio pasaporte si el usuario no tiene uno emitido', function (): void {
    $repository = new InMemoryRoadPassportRepository;

    expect(fn () => (new GetMyRoadPassportHandler($repository))->handle(new GetMyRoadPassportQuery((string) Str::uuid())))
        ->toThrow(RoadPassportNotFound::class);
});
