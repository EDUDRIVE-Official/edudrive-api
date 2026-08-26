<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Exceptions\InvalidSimulatorTransition;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorHistoryEntry;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

function newSimulator(?string $location = null): Simulator
{
    return Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: $location,
        integrationKey: IntegrationKey::generate(),
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('se registra en estado active y sin historial', function (): void {
    $simulator = newSimulator();

    expect($simulator->status())->toBe(SimulatorStatus::Active)
        ->and($simulator->history())->toBe([])
        ->and($simulator->location())->toBeNull();
});

it('acepta una ubicacion opcional', function (): void {
    $simulator = newSimulator('Sede Cartago');

    expect($simulator->location())->toBe('Sede Cartago');
});

it('suspende un simulador activo y registra el cambio en el historial', function (): void {
    $simulator = newSimulator();

    $simulator->suspend('Mantenimiento', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));

    expect($simulator->status())->toBe(SimulatorStatus::Suspended)
        ->and($simulator->history())->toHaveCount(1);

    $entry = $simulator->history()[0];
    expect($entry->fromStatus)->toBe(SimulatorStatus::Active)
        ->and($entry->toStatus)->toBe(SimulatorStatus::Suspended)
        ->and($entry->reason)->toBe('Mantenimiento');
});

it('rechaza suspender un simulador que ya esta suspendido', function (): void {
    $simulator = newSimulator();
    $simulator->suspend(null, new DateTimeImmutable('now'));

    expect(fn () => $simulator->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('rechaza suspender un simulador retirado', function (): void {
    $simulator = newSimulator();
    $simulator->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $simulator->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('reactiva un simulador suspendido', function (): void {
    $simulator = newSimulator();
    $simulator->suspend(null, new DateTimeImmutable('now'));

    $simulator->reactivate(new DateTimeImmutable('2026-08-28T00:00:00+00:00'));

    expect($simulator->status())->toBe(SimulatorStatus::Active)
        ->and($simulator->history())->toHaveCount(2);
});

it('rechaza reactivar un simulador activo', function (): void {
    $simulator = newSimulator();

    expect(fn () => $simulator->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('rechaza reactivar un simulador retirado', function (): void {
    $simulator = newSimulator();
    $simulator->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $simulator->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('retira un simulador activo o suspendido', function (): void {
    $activeSimulator = newSimulator();
    $activeSimulator->retire('Fin de vida util', new DateTimeImmutable('now'));
    expect($activeSimulator->status())->toBe(SimulatorStatus::Retired);

    $suspendedSimulator = newSimulator();
    $suspendedSimulator->suspend(null, new DateTimeImmutable('now'));
    $suspendedSimulator->retire(null, new DateTimeImmutable('now'));
    expect($suspendedSimulator->status())->toBe(SimulatorStatus::Retired);
});

it('rechaza retirar un simulador ya retirado', function (): void {
    $simulator = newSimulator();
    $simulator->retire(null, new DateTimeImmutable('now'));

    expect(fn () => $simulator->retire(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('rota la llave de integracion sin registrar historial', function (): void {
    $simulator = newSimulator();
    $originalHash = $simulator->integrationKey()->hash();
    $newKey = IntegrationKey::generate();

    $simulator->rotateIntegrationKey($newKey);

    expect($simulator->integrationKey()->hash())->toBe($newKey->hash())
        ->and($simulator->integrationKey()->hash())->not->toBe($originalHash)
        ->and($simulator->history())->toBe([]);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = SimulatorId::fromString((string) Str::uuid());
    $deviceIdentifier = DeviceIdentifier::fromString('SIM-00042');
    $integrationKey = IntegrationKey::fromHash(hash('sha256', 'valor-secreto'));
    $registeredAt = new DateTimeImmutable('2026-08-26T10:00:00+00:00');
    $historyEntry = SimulatorHistoryEntry::restore(
        SimulatorStatus::Active,
        SimulatorStatus::Retired,
        new DateTimeImmutable('2026-08-27T00:00:00+00:00'),
        'Motivo',
    );

    $simulator = Simulator::restore(
        id: $id,
        deviceIdentifier: $deviceIdentifier,
        softwareVersion: '2.3.1',
        location: 'Sede Cartago',
        status: SimulatorStatus::Retired,
        integrationKey: $integrationKey,
        registeredAt: $registeredAt,
        history: [$historyEntry],
    );

    expect($simulator->id()->equals($id))->toBeTrue()
        ->and($simulator->deviceIdentifier()->equals($deviceIdentifier))->toBeTrue()
        ->and($simulator->softwareVersion())->toBe('2.3.1')
        ->and($simulator->location())->toBe('Sede Cartago')
        ->and($simulator->status())->toBe(SimulatorStatus::Retired)
        ->and($simulator->integrationKey()->hash())->toBe($integrationKey->hash())
        ->and($simulator->registeredAt())->toBe($registeredAt)
        ->and($simulator->history())->toBe([$historyEntry]);
});
