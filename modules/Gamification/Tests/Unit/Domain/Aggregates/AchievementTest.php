<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Enums\AchievementStatus;
use Modules\Gamification\Domain\Exceptions\InvalidAchievementTransition;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

function newAchievement(): Achievement
{
    return Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString('primer-curso-completado'),
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se crea activo', function (): void {
    $achievement = newAchievement();

    expect($achievement->status())->toBe(AchievementStatus::Active)
        ->and($achievement->retiredAt())->toBeNull()
        ->and($achievement->retiredReason())->toBeNull();
});

it('se retira y registra el motivo y la fecha', function (): void {
    $achievement = newAchievement();

    $achievement->retire('Reemplazado por un logro mejor definido', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($achievement->status())->toBe(AchievementStatus::Retired)
        ->and($achievement->retiredReason())->toBe('Reemplazado por un logro mejor definido')
        ->and($achievement->retiredAt())->not->toBeNull();
});

it('rechaza retirar un logro ya retirado', function (): void {
    $achievement = newAchievement();
    $achievement->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $achievement->retire(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidAchievementTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AchievementId::fromString((string) Str::uuid());
    $code = AchievementCode::fromString('primer-curso-completado');
    $registeredAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $retiredAt = new DateTimeImmutable('2026-08-27T00:00:00+00:00');

    $achievement = Achievement::restore(
        id: $id,
        code: $code,
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
        status: AchievementStatus::Retired,
        registeredAt: $registeredAt,
        retiredAt: $retiredAt,
        retiredReason: 'Motivo',
    );

    expect($achievement->id()->equals($id))->toBeTrue()
        ->and($achievement->code()->equals($code))->toBeTrue()
        ->and($achievement->name())->toBe('Primer curso completado')
        ->and($achievement->description())->toBe('Se otorga al completar el primer curso.')
        ->and($achievement->earningRule())->toBe('Completar cualquier curso por primera vez.')
        ->and($achievement->status())->toBe(AchievementStatus::Retired)
        ->and($achievement->registeredAt())->toBe($registeredAt)
        ->and($achievement->retiredAt())->toBe($retiredAt)
        ->and($achievement->retiredReason())->toBe('Motivo');
});
