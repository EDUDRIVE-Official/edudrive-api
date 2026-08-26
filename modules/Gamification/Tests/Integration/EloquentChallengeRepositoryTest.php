<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Enums\ChallengeStatus;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

uses(RefreshDatabase::class);

function newPersistableChallenge(?string $code = null): Challenge
{
    return Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString($code ?? 'RETO-'.strtoupper((string) Str::random(6))),
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Individual,
        reward: '100 puntos de experiencia.',
        startsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-09-08T00:00:00+00:00'),
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera un reto por identificador', function (): void {
    $challenge = newPersistableChallenge();

    app(ChallengeRepository::class)->save($challenge);
    $found = app(ChallengeRepository::class)->findById($challenge->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($challenge->id()))->toBeTrue()
        ->and($found?->code()->equals($challenge->code()))->toBeTrue()
        ->and($found?->type())->toBe(ChallengeType::Individual)
        ->and($found?->status())->toBe(ChallengeStatus::Active)
        ->and($found?->retiredAt())->toBeNull();
});

it('guarda y recupera un reto retirado con su motivo', function (): void {
    $challenge = newPersistableChallenge();
    $challenge->retire('Motivo de retiro', new DateTimeImmutable('2026-09-09T00:00:00+00:00'));

    app(ChallengeRepository::class)->save($challenge);
    $found = app(ChallengeRepository::class)->findById($challenge->id());

    expect($found?->status())->toBe(ChallengeStatus::Retired)
        ->and($found?->retiredReason())->toBe('Motivo de retiro')
        ->and($found?->retiredAt())->not->toBeNull();
});

it('encuentra un reto por su codigo', function (): void {
    $challenge = newPersistableChallenge('RETO-UNICO-001');
    app(ChallengeRepository::class)->save($challenge);

    $found = app(ChallengeRepository::class)->findByCode(ChallengeCode::fromString('reto-unico-001'));

    expect($found?->id()->equals($challenge->id()))->toBeTrue();
    expect(app(ChallengeRepository::class)->findByCode(ChallengeCode::fromString('RETO-INEXISTENTE')))->toBeNull();
});

it('lista todos los retos registrados', function (): void {
    $repository = app(ChallengeRepository::class);
    $repository->save(newPersistableChallenge());
    $repository->save(newPersistableChallenge());

    expect($repository->all())->toHaveCount(2);
});
