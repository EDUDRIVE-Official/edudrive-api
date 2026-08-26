<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Enums\BadgeStatus;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

uses(RefreshDatabase::class);

function newPersistableBadge(?string $code = null): Badge
{
    return Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString($code ?? 'INSIGNIA-'.strtoupper((string) Str::random(6))),
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera una insignia por identificador', function (): void {
    $badge = newPersistableBadge();

    app(BadgeRepository::class)->save($badge);
    $found = app(BadgeRepository::class)->findById($badge->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($badge->id()))->toBeTrue()
        ->and($found?->code()->equals($badge->code()))->toBeTrue()
        ->and($found?->name())->toBe('Conductor defensivo')
        ->and($found?->category())->toBe(BadgeCategory::Practical)
        ->and($found?->level())->toBe(BadgeLevel::Bronze)
        ->and($found?->version())->toBe(1)
        ->and($found?->status())->toBe(BadgeStatus::Active)
        ->and($found?->retiredAt())->toBeNull();
});

it('guarda y recupera una insignia editada con su version incrementada', function (): void {
    $badge = newPersistableBadge();
    $badge->updateContent(
        name: 'Conductor defensivo avanzado',
        description: 'Descripcion actualizada.',
        criteria: 'Completar 20 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Silver,
    );

    app(BadgeRepository::class)->save($badge);
    $found = app(BadgeRepository::class)->findById($badge->id());

    expect($found?->version())->toBe(2)
        ->and($found?->level())->toBe(BadgeLevel::Silver);
});

it('guarda y recupera una insignia retirada con su motivo', function (): void {
    $badge = newPersistableBadge();
    $badge->retire('Motivo de retiro', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    app(BadgeRepository::class)->save($badge);
    $found = app(BadgeRepository::class)->findById($badge->id());

    expect($found?->status())->toBe(BadgeStatus::Retired)
        ->and($found?->retiredReason())->toBe('Motivo de retiro')
        ->and($found?->retiredAt())->not->toBeNull();
});

it('encuentra una insignia por su codigo', function (): void {
    $badge = newPersistableBadge('INSIGNIA-UNICA-001');
    app(BadgeRepository::class)->save($badge);

    $found = app(BadgeRepository::class)->findByCode(BadgeCode::fromString('insignia-unica-001'));

    expect($found?->id()->equals($badge->id()))->toBeTrue();
    expect(app(BadgeRepository::class)->findByCode(BadgeCode::fromString('INSIGNIA-INEXISTENTE')))->toBeNull();
});

it('lista todas las insignias registradas', function (): void {
    $repository = app(BadgeRepository::class);
    $repository->save(newPersistableBadge());
    $repository->save(newPersistableBadge());

    expect($repository->all())->toHaveCount(2);
});
