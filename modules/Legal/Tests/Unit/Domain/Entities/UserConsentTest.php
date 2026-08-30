<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Exceptions\ConsentAlreadyRevoked;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

it('se acepta con la fecha por defecto', function (): void {
    $consent = UserConsent::accept(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    );

    expect($consent->policyVersion())->toBe(1)
        ->and($consent->policyKey()->value())->toBe('privacy_policy')
        ->and($consent->acceptedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('restaura el consentimiento completo desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $acceptedAt = new DateTimeImmutable('2026-08-28T10:00:00+00:00');

    $consent = UserConsent::restore(
        id: $id,
        userId: $userId,
        policyKey: PolicyKey::fromString('terms_of_service'),
        policyVersion: 2,
        acceptedAt: $acceptedAt,
    );

    expect($consent->id())->toBe($id)
        ->and($consent->userId())->toBe($userId)
        ->and($consent->policyKey()->value())->toBe('terms_of_service')
        ->and($consent->policyVersion())->toBe(2)
        ->and($consent->acceptedAt())->toBe($acceptedAt);
});

it('no esta revocado por defecto', function (): void {
    $consent = UserConsent::accept(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    );

    expect($consent->isRevoked())->toBeFalse()
        ->and($consent->revokedAt())->toBeNull();
});

it('se revoca y registra la fecha', function (): void {
    $consent = UserConsent::accept(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    );
    $revokedAt = new DateTimeImmutable('2026-08-30 10:00:00');

    $consent->revoke($revokedAt);

    expect($consent->isRevoked())->toBeTrue()
        ->and($consent->revokedAt())->toEqual($revokedAt);
});

it('rechaza revocar un consentimiento ya revocado', function (): void {
    $consent = UserConsent::accept(
        id: (string) Str::uuid(),
        userId: (string) Str::uuid(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    );
    $consent->revoke(new DateTimeImmutable('2026-08-30 10:00:00'));

    $consent->revoke(new DateTimeImmutable('2026-08-30 11:00:00'));
})->throws(ConsentAlreadyRevoked::class);
