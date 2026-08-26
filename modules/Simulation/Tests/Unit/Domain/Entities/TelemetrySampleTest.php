<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Entities\TelemetrySample;

function newTelemetrySample(array $overrides = []): TelemetrySample
{
    return TelemetrySample::record(
        id: $overrides['id'] ?? (string) Str::uuid(),
        sessionId: $overrides['sessionId'] ?? (string) Str::uuid(),
        speedKph: $overrides['speedKph'] ?? 45.5,
        brakingPercentage: $overrides['brakingPercentage'] ?? 0.0,
        accelerationMps2: $overrides['accelerationMps2'] ?? 1.2,
        steeringAngleDegrees: $overrides['steeringAngleDegrees'] ?? -5.0,
        recordedAt: $overrides['recordedAt'] ?? new DateTimeImmutable('2026-09-01T10:10:00+00:00'),
    );
}

it('registra una lectura de telemetria valida', function (): void {
    $sample = newTelemetrySample();

    expect($sample->speedKph())->toBe(45.5)
        ->and($sample->brakingPercentage())->toBe(0.0)
        ->and($sample->accelerationMps2())->toBe(1.2)
        ->and($sample->steeringAngleDegrees())->toBe(-5.0);
});

it('rechaza una velocidad negativa', function (): void {
    expect(fn () => newTelemetrySample(['speedKph' => -1.0]))
        ->toThrow(InvalidArgumentException::class);
});

it('rechaza un porcentaje de frenado fuera de 0-100', function (): void {
    expect(fn () => newTelemetrySample(['brakingPercentage' => -0.01]))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => newTelemetrySample(['brakingPercentage' => 100.01]))
        ->toThrow(InvalidArgumentException::class);
});

it('acepta los limites exactos del porcentaje de frenado', function (): void {
    expect(newTelemetrySample(['brakingPercentage' => 0.0])->brakingPercentage())->toBe(0.0);
    expect(newTelemetrySample(['brakingPercentage' => 100.0])->brakingPercentage())->toBe(100.0);
});
