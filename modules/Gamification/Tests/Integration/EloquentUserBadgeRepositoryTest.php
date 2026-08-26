<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedBadgeForGrant(): Badge
{
    $badge = Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString('INSIGNIA-'.strtoupper((string) Str::random(6))),
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
    );
    app(BadgeRepository::class)->save($badge);

    return $badge;
}

function persistedUserForBadgeGrant(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de insignias',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera una insignia otorgada a un usuario', function (): void {
    $badge = persistedBadgeForGrant();
    $userId = persistedUserForBadgeGrant();
    $userBadge = UserBadge::grant(
        id: (string) Str::uuid(),
        badgeId: $badge->id()->value(),
        userId: $userId,
        awardedVersion: $badge->version(),
        evidence: 'Completó 10 sesiones prácticas sin infracciones.',
        earnedAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );

    app(UserBadgeRepository::class)->save($userBadge);
    $found = app(UserBadgeRepository::class)->findByBadgeAndUser($badge->id()->value(), $userId);

    expect($found)->not->toBeNull()
        ->and($found?->awardedVersion())->toBe(1)
        ->and($found?->evidence())->toBe('Completó 10 sesiones prácticas sin infracciones.');
});

it('lista todas las insignias obtenidas por un usuario', function (): void {
    $userId = persistedUserForBadgeGrant();
    $repository = app(UserBadgeRepository::class);

    $repository->save(UserBadge::grant((string) Str::uuid(), persistedBadgeForGrant()->id()->value(), $userId, 1, 'Evidencia 1', new DateTimeImmutable('now')));
    $repository->save(UserBadge::grant((string) Str::uuid(), persistedBadgeForGrant()->id()->value(), $userId, 1, 'Evidencia 2', new DateTimeImmutable('now')));
    $repository->save(UserBadge::grant((string) Str::uuid(), persistedBadgeForGrant()->id()->value(), persistedUserForBadgeGrant(), 1, 'Evidencia de otro usuario', new DateTimeImmutable('now')));

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('no encuentra un otorgamiento inexistente', function (): void {
    $badge = persistedBadgeForGrant();
    $userId = persistedUserForBadgeGrant();

    expect(app(UserBadgeRepository::class)->findByBadgeAndUser($badge->id()->value(), $userId))->toBeNull();
});
