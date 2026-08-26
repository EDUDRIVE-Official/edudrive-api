<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\UserBadge;

it('registra el otorgamiento de una insignia con su version y evidencia', function (): void {
    $earnedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');

    $userBadge = UserBadge::grant(
        id: (string) Str::uuid(),
        badgeId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        awardedVersion: 2,
        evidence: 'Completó 10 sesiones prácticas sin infracciones.',
        earnedAt: $earnedAt,
    );

    expect($userBadge->awardedVersion())->toBe(2)
        ->and($userBadge->evidence())->toBe('Completó 10 sesiones prácticas sin infracciones.')
        ->and($userBadge->earnedAt())->toBe($earnedAt);
});
