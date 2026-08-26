<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Enums\ChallengeParticipationStatus;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

function persistedChallengeForParticipation(): Challenge
{
    $challenge = Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString('RETO-'.strtoupper((string) Str::random(6))),
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Individual,
        reward: '100 puntos de experiencia.',
        startsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-09-08T00:00:00+00:00'),
    );
    app(ChallengeRepository::class)->save($challenge);

    return $challenge;
}

function persistedUserForParticipation(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de retos',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera una participacion en estado joined', function (): void {
    $challenge = persistedChallengeForParticipation();
    $userId = persistedUserForParticipation();
    $participation = ChallengeParticipation::join(
        id: (string) Str::uuid(),
        challengeId: $challenge->id()->value(),
        userId: $userId,
        joinedAt: new DateTimeImmutable('2026-09-02T00:00:00+00:00'),
    );

    app(ChallengeParticipationRepository::class)->save($participation);
    $found = app(ChallengeParticipationRepository::class)->findByChallengeAndUser($challenge->id()->value(), $userId);

    expect($found)->not->toBeNull()
        ->and($found?->status())->toBe(ChallengeParticipationStatus::Joined)
        ->and($found?->completedAt())->toBeNull();
});

it('guarda y recupera una participacion completada con su evidencia', function (): void {
    $challenge = persistedChallengeForParticipation();
    $userId = persistedUserForParticipation();
    $participation = ChallengeParticipation::join((string) Str::uuid(), $challenge->id()->value(), $userId, new DateTimeImmutable('2026-09-02T00:00:00+00:00'));
    $participation->complete('Completó las cinco sesiones sin infracciones.', new DateTimeImmutable('2026-09-07T00:00:00+00:00'));

    app(ChallengeParticipationRepository::class)->save($participation);
    $found = app(ChallengeParticipationRepository::class)->findByChallengeAndUser($challenge->id()->value(), $userId);

    expect($found?->status())->toBe(ChallengeParticipationStatus::Completed)
        ->and($found?->evidence())->toBe('Completó las cinco sesiones sin infracciones.')
        ->and($found?->completedAt())->not->toBeNull();
});

it('lista todas las participaciones de un usuario', function (): void {
    $userId = persistedUserForParticipation();
    $repository = app(ChallengeParticipationRepository::class);

    $repository->save(ChallengeParticipation::join((string) Str::uuid(), persistedChallengeForParticipation()->id()->value(), $userId, new DateTimeImmutable('now')));
    $repository->save(ChallengeParticipation::join((string) Str::uuid(), persistedChallengeForParticipation()->id()->value(), $userId, new DateTimeImmutable('now')));
    $repository->save(ChallengeParticipation::join((string) Str::uuid(), persistedChallengeForParticipation()->id()->value(), persistedUserForParticipation(), new DateTimeImmutable('now')));

    expect($repository->allForUser($userId))->toHaveCount(2);
});

it('no encuentra una participacion inexistente', function (): void {
    $challenge = persistedChallengeForParticipation();
    $userId = persistedUserForParticipation();

    expect(app(ChallengeParticipationRepository::class)->findByChallengeAndUser($challenge->id()->value(), $userId))->toBeNull();
});
