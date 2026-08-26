<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Enums\ChallengeStatus;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Exceptions\InvalidChallengeTransition;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

function newChallenge(
    DateTimeImmutable $startsAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
    DateTimeImmutable $endsAt = new DateTimeImmutable('2026-09-08T00:00:00+00:00'),
): Challenge {
    return Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString('semana-manejo-seguro'),
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Individual,
        reward: '100 puntos de experiencia.',
        startsAt: $startsAt,
        endsAt: $endsAt,
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se crea activo con su tipo y ventana de fechas', function (): void {
    $challenge = newChallenge();

    expect($challenge->status())->toBe(ChallengeStatus::Active)
        ->and($challenge->type())->toBe(ChallengeType::Individual)
        ->and($challenge->retiredAt())->toBeNull()
        ->and($challenge->retiredReason())->toBeNull();
});

it('rechaza una fecha de fin anterior o igual a la fecha de inicio', function (): void {
    $startsAt = new DateTimeImmutable('2026-09-08T00:00:00+00:00');
    $endsAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');

    expect(fn () => newChallenge($startsAt, $endsAt))->toThrow(InvalidArgumentException::class);
});

it('confirma que una fecha esta dentro de la ventana de vigencia', function (): void {
    $challenge = newChallenge();

    expect($challenge->isWithinWindow(new DateTimeImmutable('2026-09-04T00:00:00+00:00')))->toBeTrue()
        ->and($challenge->isWithinWindow(new DateTimeImmutable('2026-08-31T00:00:00+00:00')))->toBeFalse()
        ->and($challenge->isWithinWindow(new DateTimeImmutable('2026-09-09T00:00:00+00:00')))->toBeFalse();
});

it('se retira y registra el motivo y la fecha', function (): void {
    $challenge = newChallenge();

    $challenge->retire('Reemplazado por un reto mejor definido', new DateTimeImmutable('2026-09-09T00:00:00+00:00'));

    expect($challenge->status())->toBe(ChallengeStatus::Retired)
        ->and($challenge->retiredReason())->toBe('Reemplazado por un reto mejor definido')
        ->and($challenge->retiredAt())->not->toBeNull();
});

it('rechaza retirar un reto ya retirado', function (): void {
    $challenge = newChallenge();
    $challenge->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $challenge->retire(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidChallengeTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = ChallengeId::fromString((string) Str::uuid());
    $code = ChallengeCode::fromString('semana-manejo-seguro');
    $startsAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00');
    $endsAt = new DateTimeImmutable('2026-09-08T00:00:00+00:00');
    $registeredAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $retiredAt = new DateTimeImmutable('2026-09-09T00:00:00+00:00');

    $challenge = Challenge::restore(
        id: $id,
        code: $code,
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Group,
        reward: '100 puntos de experiencia.',
        startsAt: $startsAt,
        endsAt: $endsAt,
        status: ChallengeStatus::Retired,
        registeredAt: $registeredAt,
        retiredAt: $retiredAt,
        retiredReason: 'Motivo',
    );

    expect($challenge->id()->equals($id))->toBeTrue()
        ->and($challenge->code()->equals($code))->toBeTrue()
        ->and($challenge->type())->toBe(ChallengeType::Group)
        ->and($challenge->startsAt())->toBe($startsAt)
        ->and($challenge->endsAt())->toBe($endsAt)
        ->and($challenge->status())->toBe(ChallengeStatus::Retired)
        ->and($challenge->registeredAt())->toBe($registeredAt)
        ->and($challenge->retiredAt())->toBe($retiredAt)
        ->and($challenge->retiredReason())->toBe('Motivo');
});
