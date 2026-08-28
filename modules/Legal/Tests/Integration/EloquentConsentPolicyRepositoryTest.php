<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Legal\Domain\Aggregates\ConsentPolicy;
use Modules\Legal\Domain\Repositories\ConsentPolicyRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

it('guarda y recupera la version vigente de una politica', function (): void {
    $repository = app(ConsentPolicyRepository::class);
    $key = PolicyKey::fromString('privacy_policy');

    $repository->save(ConsentPolicy::publish(id: (string) Str::uuid(), key: $key, version: 1));
    $repository->save(ConsentPolicy::publish(id: (string) Str::uuid(), key: $key, version: 2));

    $current = $repository->findCurrentByKey($key);

    expect($current)->not->toBeNull()
        ->and($current?->version())->toBe(2);
});

it('no encuentra una politica inexistente', function (): void {
    $repository = app(ConsentPolicyRepository::class);

    expect($repository->findCurrentByKey(PolicyKey::fromString('inexistente')))->toBeNull();
});

it('lista la version vigente de cada politica registrada', function (): void {
    $repository = app(ConsentPolicyRepository::class);

    $repository->save(ConsentPolicy::publish(id: (string) Str::uuid(), key: PolicyKey::fromString('privacy_policy'), version: 1));
    $repository->save(ConsentPolicy::publish(id: (string) Str::uuid(), key: PolicyKey::fromString('privacy_policy'), version: 2));
    $repository->save(ConsentPolicy::publish(id: (string) Str::uuid(), key: PolicyKey::fromString('terms_of_service'), version: 1));

    $current = $repository->allCurrent();

    expect($current)->toHaveCount(2);

    $versions = [];
    foreach ($current as $policy) {
        $versions[$policy->key()->value()] = $policy->version();
    }

    expect($versions['privacy_policy'])->toBe(2)
        ->and($versions['terms_of_service'])->toBe(1);
});
