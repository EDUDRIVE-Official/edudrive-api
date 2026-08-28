<?php

declare(strict_types=1);

use Modules\Legal\Domain\ValueObjects\PolicyKey;

it('acepta una clave valida en minusculas con guiones bajos', function (): void {
    $key = PolicyKey::fromString('privacy_policy');

    expect($key->value())->toBe('privacy_policy');
});

it('rechaza una clave que inicia con un numero', function (): void {
    expect(fn () => PolicyKey::fromString('1policy'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza una clave con mayusculas', function (): void {
    expect(fn () => PolicyKey::fromString('Privacy_Policy'))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza una clave que supera 100 caracteres', function (): void {
    expect(fn () => PolicyKey::fromString(str_repeat('a', 101)))
        ->toThrow(InvalidArgumentException::class);
});

it('compara claves por su valor', function (): void {
    expect(PolicyKey::fromString('terms_of_service')->equals(PolicyKey::fromString('terms_of_service')))->toBeTrue()
        ->and(PolicyKey::fromString('terms_of_service')->equals(PolicyKey::fromString('privacy_policy')))->toBeFalse();
});
