<?php

declare(strict_types=1);

use Modules\Simulation\Domain\ValueObjects\IntegrationKey;

it('genera una llave con valor plano y hash', function (): void {
    $key = IntegrationKey::generate();

    expect($key->plainValue())->not->toBeNull()
        ->and($key->plainValue())->toMatch('/^[0-9a-f]{64}$/')
        ->and($key->hash())->toMatch('/^[0-9a-f]{64}$/')
        ->and($key->hash())->toBe(hash('sha256', (string) $key->plainValue()));
});

it('genera llaves distintas en cada llamada', function (): void {
    expect(IntegrationKey::generate()->plainValue())->not->toBe(IntegrationKey::generate()->plainValue());
});

it('reconstruye desde un hash sin valor plano', function (): void {
    $key = IntegrationKey::generate();
    $restored = IntegrationKey::fromHash($key->hash());

    expect($restored->plainValue())->toBeNull()
        ->and($restored->hash())->toBe($key->hash());
});

it('verifica que un candidato coincida con la llave', function (): void {
    $key = IntegrationKey::generate();
    $restored = IntegrationKey::fromHash($key->hash());

    expect($restored->matches((string) $key->plainValue()))->toBeTrue()
        ->and($restored->matches('un-valor-incorrecto'))->toBeFalse();
});
