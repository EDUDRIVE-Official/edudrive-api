<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedAchievementForGrant(): Achievement
{
    $achievement = Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString('LOGRO-'.strtoupper((string) Str::random(6))),
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
    );
    app(AchievementRepository::class)->save($achievement);

    return $achievement;
}

function persistedUserForGrant(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de logros',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera un logro otorgado a un usuario', function (): void {
    $achievement = persistedAchievementForGrant();
    $userId = persistedUserForGrant();
    $userAchievement = UserAchievement::grant(
        id: (string) Str::uuid(),
        achievementId: $achievement->id()->value(),
        userId: $userId,
        evidence: 'Completó el curso de manejo defensivo con 95% de aciertos.',
        earnedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );

    app(UserAchievementRepository::class)->save($userAchievement);
    $found = app(UserAchievementRepository::class)->findByAchievementAndUser($achievement->id()->value(), $userId);

    expect($found)->not->toBeNull()
        ->and($found?->evidence())->toBe('Completó el curso de manejo defensivo con 95% de aciertos.');
});

it('lista todos los logros obtenidos por un usuario', function (): void {
    $userId = persistedUserForGrant();
    $repository = app(UserAchievementRepository::class);

    $repository->save(UserAchievement::grant((string) Str::uuid(), persistedAchievementForGrant()->id()->value(), $userId, 'Evidencia 1', new DateTimeImmutable('now')));
    $repository->save(UserAchievement::grant((string) Str::uuid(), persistedAchievementForGrant()->id()->value(), $userId, 'Evidencia 2', new DateTimeImmutable('now')));
    $repository->save(UserAchievement::grant((string) Str::uuid(), persistedAchievementForGrant()->id()->value(), persistedUserForGrant(), 'Evidencia de otro usuario', new DateTimeImmutable('now')));

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('no encuentra un otorgamiento inexistente', function (): void {
    $achievement = persistedAchievementForGrant();
    $userId = persistedUserForGrant();

    expect(app(UserAchievementRepository::class)->findByAchievementAndUser($achievement->id()->value(), $userId))->toBeNull();
});
