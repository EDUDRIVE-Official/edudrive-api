<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Entities\TelemetrySample;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

uses(RefreshDatabase::class);

function persistedTelemetrySessionId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de telemetria',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $user->id(),
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    app(SimulationSessionRepository::class)->save($session);

    return $session->id()->value();
}

it('guarda y recupera lecturas de telemetria en lote', function (): void {
    $sessionId = persistedTelemetrySessionId();
    $samples = [
        TelemetrySample::record((string) Str::uuid(), $sessionId, 40.0, 0.0, 1.1, 0.0, new DateTimeImmutable('2026-09-01T10:10:00+00:00')),
        TelemetrySample::record((string) Str::uuid(), $sessionId, 42.5, 10.0, -0.5, 3.0, new DateTimeImmutable('2026-09-01T10:10:05+00:00')),
    ];

    app(TelemetrySampleRepository::class)->saveBatch($samples);
    $found = app(TelemetrySampleRepository::class)->allForSession($sessionId);

    expect($found)->toHaveCount(2)
        ->and($found[0]->speedKph())->toBe(40.0)
        ->and($found[1]->brakingPercentage())->toBe(10.0);
});

it('no falla al guardar un lote vacio de lecturas', function (): void {
    app(TelemetrySampleRepository::class)->saveBatch([]);

    expect(true)->toBeTrue();
});

it('guarda y recupera eventos de telemetria en lote', function (): void {
    $sessionId = persistedTelemetrySessionId();
    $events = [
        TelemetryEvent::record((string) Str::uuid(), $sessionId, TelemetryEventType::Collision, 'Colision leve', new DateTimeImmutable('2026-09-01T10:12:00+00:00')),
        TelemetryEvent::record((string) Str::uuid(), $sessionId, TelemetryEventType::SignalUsage, null, new DateTimeImmutable('2026-09-01T10:13:00+00:00')),
    ];

    app(TelemetryEventRepository::class)->saveBatch($events);
    $found = app(TelemetryEventRepository::class)->allForSession($sessionId);

    expect($found)->toHaveCount(2)
        ->and($found[0]->type())->toBe(TelemetryEventType::Collision)
        ->and($found[0]->details())->toBe('Colision leve')
        ->and($found[1]->details())->toBeNull();
});

it('encuentra un simulador por el hash de su llave de integracion', function (): void {
    $integrationKey = IntegrationKey::generate();
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: $integrationKey,
    );
    app(SimulatorRepository::class)->save($simulator);

    $found = app(SimulatorRepository::class)->findByIntegrationKeyHash($integrationKey->hash());

    expect($found?->id()->equals($simulator->id()))->toBeTrue();
    expect(app(SimulatorRepository::class)->findByIntegrationKeyHash('hash-inexistente'))->toBeNull();
});
