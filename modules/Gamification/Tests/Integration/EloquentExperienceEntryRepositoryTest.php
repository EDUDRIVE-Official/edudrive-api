<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedExperienceUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de experiencia',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera un registro de experiencia con su competencia', function (): void {
    $userId = persistedExperienceUserId();
    $entry = ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: $userId,
        points: 50,
        competencyId: 'manejo-defensivo',
        reason: 'Completó la sesión práctica sin infracciones.',
        recordedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );

    app(ExperienceEntryRepository::class)->save($entry);
    $found = app(ExperienceEntryRepository::class)->allForUser($userId);

    expect($found)->toHaveCount(1)
        ->and($found[0]->points())->toBe(50)
        ->and($found[0]->competencyId())->toBe('manejo-defensivo')
        ->and($found[0]->reason())->toBe('Completó la sesión práctica sin infracciones.');
});

it('guarda y recupera un registro de experiencia sin competencia', function (): void {
    $userId = persistedExperienceUserId();
    $entry = ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: $userId,
        points: 10,
        competencyId: null,
        reason: 'Participación general.',
        recordedAt: new DateTimeImmutable('now'),
    );

    app(ExperienceEntryRepository::class)->save($entry);
    $found = app(ExperienceEntryRepository::class)->allForUser($userId);

    expect($found[0]->competencyId())->toBeNull();
});

it('lista solo los registros del usuario solicitado', function (): void {
    $userId = persistedExperienceUserId();
    $otherUserId = persistedExperienceUserId();
    $repository = app(ExperienceEntryRepository::class);

    $repository->save(ExperienceEntry::record((string) Str::uuid(), $userId, 10, null, 'Motivo 1', new DateTimeImmutable('now')));
    $repository->save(ExperienceEntry::record((string) Str::uuid(), $userId, 20, null, 'Motivo 2', new DateTimeImmutable('now')));
    $repository->save(ExperienceEntry::record((string) Str::uuid(), $otherUserId, 30, null, 'Motivo de otro usuario', new DateTimeImmutable('now')));

    expect($repository->allForUser($userId))->toHaveCount(2);
});
