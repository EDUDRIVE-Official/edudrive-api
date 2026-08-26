<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Enums\ChallengeParticipationStatus;
use Modules\Gamification\Domain\Exceptions\InvalidChallengeParticipationTransition;

function newChallengeParticipation(): ChallengeParticipation
{
    return ChallengeParticipation::join(
        id: (string) Str::uuid(),
        challengeId: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        joinedAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
    );
}

it('se une con estado joined y sin finalizacion', function (): void {
    $participation = newChallengeParticipation();

    expect($participation->status())->toBe(ChallengeParticipationStatus::Joined)
        ->and($participation->completedAt())->toBeNull()
        ->and($participation->evidence())->toBeNull();
});

it('se completa y registra la evidencia y la fecha', function (): void {
    $participation = newChallengeParticipation();

    $participation->complete('Completó las cinco sesiones sin infracciones.', new DateTimeImmutable('2026-09-07T00:00:00+00:00'));

    expect($participation->status())->toBe(ChallengeParticipationStatus::Completed)
        ->and($participation->evidence())->toBe('Completó las cinco sesiones sin infracciones.')
        ->and($participation->completedAt())->not->toBeNull();
});

it('rechaza completar una participacion ya completada', function (): void {
    $participation = newChallengeParticipation();
    $participation->complete(null, new DateTimeImmutable('now'));

    expect(fn () => $participation->complete(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidChallengeParticipationTransition::class);
});

it('restaura la entidad completa desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $challengeId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $joinedAt = new DateTimeImmutable('2026-09-01T10:00:00+00:00');
    $completedAt = new DateTimeImmutable('2026-09-07T00:00:00+00:00');

    $participation = ChallengeParticipation::restore(
        id: $id,
        challengeId: $challengeId,
        userId: $userId,
        status: ChallengeParticipationStatus::Completed,
        joinedAt: $joinedAt,
        completedAt: $completedAt,
        evidence: 'Evidencia',
    );

    expect($participation->id())->toBe($id)
        ->and($participation->challengeId())->toBe($challengeId)
        ->and($participation->userId())->toBe($userId)
        ->and($participation->status())->toBe(ChallengeParticipationStatus::Completed)
        ->and($participation->joinedAt())->toBe($joinedAt)
        ->and($participation->completedAt())->toBe($completedAt)
        ->and($participation->evidence())->toBe('Evidencia');
});
