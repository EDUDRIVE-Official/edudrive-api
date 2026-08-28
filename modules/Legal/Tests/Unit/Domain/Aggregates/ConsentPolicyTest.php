<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

it('se publica con la fecha de vigencia por defecto', function (): void {
    $policy = ConsentPolicy::publish(
        id: (string) Str::uuid(),
        key: PolicyKey::fromString('privacy_policy'),
        version: 1,
    );

    expect($policy->version())->toBe(1)
        ->and($policy->key()->value())->toBe('privacy_policy')
        ->and($policy->effectiveAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = (string) Str::uuid();
    $effectiveAt = new DateTimeImmutable('2026-08-28T10:00:00+00:00');

    $policy = ConsentPolicy::restore(
        id: $id,
        key: PolicyKey::fromString('terms_of_service'),
        version: 3,
        effectiveAt: $effectiveAt,
    );

    expect($policy->id())->toBe($id)
        ->and($policy->key()->value())->toBe('terms_of_service')
        ->and($policy->version())->toBe(3)
        ->and($policy->effectiveAt())->toBe($effectiveAt);
});
