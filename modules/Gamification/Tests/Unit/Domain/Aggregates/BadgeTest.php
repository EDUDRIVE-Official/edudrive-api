<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Enums\BadgeStatus;
use Modules\Gamification\Domain\Exceptions\InvalidBadgeTransition;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

function newBadge(): Badge
{
    return Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString('conductor-defensivo'),
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se crea activa en version uno', function (): void {
    $badge = newBadge();

    expect($badge->status())->toBe(BadgeStatus::Active)
        ->and($badge->version())->toBe(1)
        ->and($badge->category())->toBe(BadgeCategory::Practical)
        ->and($badge->level())->toBe(BadgeLevel::Bronze)
        ->and($badge->retiredAt())->toBeNull()
        ->and($badge->retiredReason())->toBeNull();
});

it('incrementa la version al actualizar el contenido', function (): void {
    $badge = newBadge();

    $badge->updateContent(
        name: 'Conductor defensivo avanzado',
        description: 'Descripcion actualizada.',
        criteria: 'Completar 20 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Silver,
    );

    expect($badge->version())->toBe(2)
        ->and($badge->name())->toBe('Conductor defensivo avanzado')
        ->and($badge->description())->toBe('Descripcion actualizada.')
        ->and($badge->criteria())->toBe('Completar 20 sesiones prácticas sin infracciones.')
        ->and($badge->level())->toBe(BadgeLevel::Silver);
});

it('rechaza actualizar el contenido de una insignia retirada', function (): void {
    $badge = newBadge();
    $badge->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $badge->updateContent(
        name: 'Nombre',
        description: 'Descripcion',
        criteria: 'Criterio',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Gold,
    ))->toThrow(InvalidBadgeTransition::class);
});

it('se retira y registra el motivo y la fecha', function (): void {
    $badge = newBadge();

    $badge->retire('Reemplazada por una insignia mejor definida', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($badge->status())->toBe(BadgeStatus::Retired)
        ->and($badge->retiredReason())->toBe('Reemplazada por una insignia mejor definida')
        ->and($badge->retiredAt())->not->toBeNull();
});

it('rechaza retirar una insignia ya retirada', function (): void {
    $badge = newBadge();
    $badge->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $badge->retire(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidBadgeTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = BadgeId::fromString((string) Str::uuid());
    $code = BadgeCode::fromString('conductor-defensivo');
    $registeredAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $retiredAt = new DateTimeImmutable('2026-08-27T00:00:00+00:00');

    $badge = Badge::restore(
        id: $id,
        code: $code,
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Gold,
        version: 3,
        status: BadgeStatus::Retired,
        registeredAt: $registeredAt,
        retiredAt: $retiredAt,
        retiredReason: 'Motivo',
    );

    expect($badge->id()->equals($id))->toBeTrue()
        ->and($badge->code()->equals($code))->toBeTrue()
        ->and($badge->name())->toBe('Conductor defensivo')
        ->and($badge->description())->toBe('Se otorga por demostrar manejo defensivo consistente.')
        ->and($badge->criteria())->toBe('Completar 10 sesiones prácticas sin infracciones.')
        ->and($badge->category())->toBe(BadgeCategory::Practical)
        ->and($badge->level())->toBe(BadgeLevel::Gold)
        ->and($badge->version())->toBe(3)
        ->and($badge->status())->toBe(BadgeStatus::Retired)
        ->and($badge->registeredAt())->toBe($registeredAt)
        ->and($badge->retiredAt())->toBe($retiredAt)
        ->and($badge->retiredReason())->toBe('Motivo');
});
