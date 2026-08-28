<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Tests\TestCase;

/** @return array{0: Simulator, 1: string} */
function persistedRateLimitingSimulator(): array
{
    $integrationKey = IntegrationKey::generate();
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: $integrationKey,
    );
    app(SimulatorRepository::class)->save($simulator);

    return [$simulator, (string) $integrationKey->plainValue()];
}

it('limita el envio de telemetria a 60 por minuto por simulador', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedRateLimitingSimulator();
    $sessionId = (string) Str::uuid();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->withToken($token)->postJson("/api/v1/simulation/sessions/{$sessionId}/telemetry", ['samples' => [], 'events' => []]);
    }

    $this->withToken($token)->postJson("/api/v1/simulation/sessions/{$sessionId}/telemetry", ['samples' => [], 'events' => []])
        ->assertStatus(429)
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});

it('limita el envio de decisiones a 60 por minuto por simulador', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedRateLimitingSimulator();
    $sessionId = (string) Str::uuid();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->withToken($token)->postJson("/api/v1/simulation/sessions/{$sessionId}/decisions", ['decisions' => []]);
    }

    $this->withToken($token)->postJson("/api/v1/simulation/sessions/{$sessionId}/decisions", ['decisions' => []])
        ->assertStatus(429);
});

it('no comparte el limite de integracion entre simuladores distintos', function (): void {
    /** @var TestCase $this */
    [, $tokenA] = persistedRateLimitingSimulator();
    [, $tokenB] = persistedRateLimitingSimulator();
    $sessionId = (string) Str::uuid();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->withToken($tokenA)->postJson("/api/v1/simulation/sessions/{$sessionId}/telemetry", ['samples' => [], 'events' => []]);
    }

    $this->withToken($tokenA)->postJson("/api/v1/simulation/sessions/{$sessionId}/telemetry", ['samples' => [], 'events' => []])
        ->assertStatus(429);

    $response = $this->withToken($tokenB)->postJson("/api/v1/simulation/sessions/{$sessionId}/telemetry", ['samples' => [], 'events' => []]);
    expect($response->status())->not->toBe(429);
});
