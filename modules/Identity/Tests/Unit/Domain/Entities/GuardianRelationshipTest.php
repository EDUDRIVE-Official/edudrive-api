<?php

declare(strict_types=1);

use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Exceptions\InvalidGuardianRelationship;

it('crea una relacion tutor-menor', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $relationship = GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
        occurredAt: $createdAt,
    );

    expect($relationship->id())->toBe('relationship-1')
        ->and($relationship->guardianUserId())->toBe('guardian-1')
        ->and($relationship->minorUserId())->toBe('minor-1')
        ->and($relationship->createdAt())->toEqual($createdAt)
        ->and($relationship->isActive())->toBeTrue();
});

it('rechaza que un usuario sea su propio tutor', function (): void {
    GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'user-1',
        minorUserId: 'user-1',
    );
})->throws(InvalidGuardianRelationship::class);

it('reconstruye una relacion desde persistencia', function (): void {
    $createdAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $relationship = GuardianRelationship::restore(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
        createdAt: $createdAt,
        revokedAt: null,
    );

    expect($relationship->isActive())->toBeTrue();
});

it('se revoca y deja de estar activa', function (): void {
    $relationship = GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    );
    $revokedAt = new DateTimeImmutable('2026-08-30 11:00:00');

    $relationship->revoke($revokedAt);

    expect($relationship->isActive())->toBeFalse()
        ->and($relationship->revokedAt())->toEqual($revokedAt);
});

it('rechaza revocar una relacion ya revocada', function (): void {
    $relationship = GuardianRelationship::create(
        id: 'relationship-1',
        guardianUserId: 'guardian-1',
        minorUserId: 'minor-1',
    );
    $relationship->revoke(new DateTimeImmutable('2026-08-30 11:00:00'));

    $relationship->revoke(new DateTimeImmutable('2026-08-30 12:00:00'));
})->throws(InvalidGuardianRelationship::class);
