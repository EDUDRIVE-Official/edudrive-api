<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Enums\AchievementStatus;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

uses(RefreshDatabase::class);

function newPersistableAchievement(?string $code = null): Achievement
{
    return Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString($code ?? 'LOGRO-'.strtoupper((string) Str::random(6))),
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera un logro por identificador', function (): void {
    $achievement = newPersistableAchievement();

    app(AchievementRepository::class)->save($achievement);
    $found = app(AchievementRepository::class)->findById($achievement->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($achievement->id()))->toBeTrue()
        ->and($found?->code()->equals($achievement->code()))->toBeTrue()
        ->and($found?->name())->toBe('Primer curso completado')
        ->and($found?->status())->toBe(AchievementStatus::Active)
        ->and($found?->retiredAt())->toBeNull();
});

it('guarda y recupera un logro retirado con su motivo', function (): void {
    $achievement = newPersistableAchievement();
    $achievement->retire('Motivo de retiro', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    app(AchievementRepository::class)->save($achievement);
    $found = app(AchievementRepository::class)->findById($achievement->id());

    expect($found?->status())->toBe(AchievementStatus::Retired)
        ->and($found?->retiredReason())->toBe('Motivo de retiro')
        ->and($found?->retiredAt())->not->toBeNull();
});

it('encuentra un logro por su codigo', function (): void {
    $achievement = newPersistableAchievement('LOGRO-UNICO-001');
    app(AchievementRepository::class)->save($achievement);

    $found = app(AchievementRepository::class)->findByCode(AchievementCode::fromString('logro-unico-001'));

    expect($found?->id()->equals($achievement->id()))->toBeTrue();
    expect(app(AchievementRepository::class)->findByCode(AchievementCode::fromString('LOGRO-INEXISTENTE')))->toBeNull();
});

it('lista todos los logros registrados', function (): void {
    $repository = app(AchievementRepository::class);
    $repository->save(newPersistableAchievement());
    $repository->save(newPersistableAchievement());

    expect($repository->all())->toHaveCount(2);
});
