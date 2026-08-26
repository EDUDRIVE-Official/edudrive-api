<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\RoadPassportStatus;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models\RoadPassportHistoryEntryModel;
use Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models\RoadPassportModel;

uses(RefreshDatabase::class);

function persistedRoadPassportUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Titular de pasaporte',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera un pasaporte vial por identificador', function (): void {
    $userId = persistedRoadPassportUserId();
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $userId,
        issuedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );

    app(RoadPassportRepository::class)->save($passport);
    $found = app(RoadPassportRepository::class)->findById($passport->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($passport->id()))->toBeTrue()
        ->and($found?->userId())->toBe($userId)
        ->and($found?->status())->toBe(RoadPassportStatus::Active)
        ->and($found?->level())->toBe(1)
        ->and($found?->issuedAt())->toEqual($passport->issuedAt())
        ->and($found?->history())->toBe([]);
});

it('guarda y recupera el historial en orden cronologico', function (): void {
    $repository = app(RoadPassportRepository::class);
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: persistedRoadPassportUserId(),
        issuedAt: new DateTimeImmutable('now'),
    );
    $passport->changeLevel(2, new DateTimeImmutable('2026-08-26T11:00:00+00:00'));
    $passport->suspend('Revision pendiente', new DateTimeImmutable('2026-08-26T12:00:00+00:00'));
    $repository->save($passport);

    $found = $repository->findById($passport->id());

    expect($found?->history())->toHaveCount(2);
    expect($found?->history()[0]->toValue)->toBe('2');
    expect($found?->history()[1]->toValue)->toBe('suspended')
        ->and($found?->history()[1]->reason)->toBe('Revision pendiente');
});

it('encuentra un pasaporte por el id de su propietario', function (): void {
    $userId = persistedRoadPassportUserId();
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $userId,
        issuedAt: new DateTimeImmutable('now'),
    );
    app(RoadPassportRepository::class)->save($passport);

    $found = app(RoadPassportRepository::class)->findByUserId($userId);

    expect($found?->id()->equals($passport->id()))->toBeTrue();
    expect(app(RoadPassportRepository::class)->findByUserId((string) Str::uuid()))->toBeNull();
});

it('reemplaza el historial en vez de duplicarlo al guardar de nuevo', function (): void {
    $repository = app(RoadPassportRepository::class);
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: persistedRoadPassportUserId(),
        issuedAt: new DateTimeImmutable('now'),
    );
    $passport->changeLevel(2, new DateTimeImmutable('now'));
    $repository->save($passport);
    $repository->save($passport);

    $found = $repository->findById($passport->id());

    expect($found?->history())->toHaveCount(1);
});

it('borra en cascada el historial al eliminar el pasaporte', function (): void {
    $repository = app(RoadPassportRepository::class);
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: persistedRoadPassportUserId(),
        issuedAt: new DateTimeImmutable('now'),
    );
    $passport->changeLevel(2, new DateTimeImmutable('now'));
    $repository->save($passport);

    RoadPassportModel::query()
        ->where('id', $passport->id()->value())
        ->delete();

    expect(RoadPassportHistoryEntryModel::query()
        ->where('road_passport_id', $passport->id()->value())
        ->count())->toBe(0);
});
