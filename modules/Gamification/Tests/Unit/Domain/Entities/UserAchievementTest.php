<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\UserAchievement;

it('registra el otorgamiento de un logro con su evidencia', function (): void {
    $earnedAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');

    $userAchievement = UserAchievement::grant(
        id: (string) Str::uuid(),
        achievementId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        evidence: 'Completó el curso de manejo defensivo con 95% de aciertos.',
        earnedAt: $earnedAt,
    );

    expect($userAchievement->evidence())->toBe('Completó el curso de manejo defensivo con 95% de aciertos.')
        ->and($userAchievement->earnedAt())->toBe($earnedAt);
});
