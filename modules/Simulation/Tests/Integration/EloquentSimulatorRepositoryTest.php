<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulatorHistoryEntryModel;
use Modules\Simulation\Infrastructure\Persistence\Eloquent\Models\SimulatorModel;

uses(RefreshDatabase::class);

function newPersistableSimulator(?string $deviceIdentifier = null): Simulator
{
    return Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString($deviceIdentifier ?? 'SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
        registeredAt: new DateTimeImmutable('2026-08-26T10:00:00+00:00'),
    );
}

it('guarda y recupera un simulador por identificador', function (): void {
    $simulator = newPersistableSimulator();

    app(SimulatorRepository::class)->save($simulator);
    $found = app(SimulatorRepository::class)->findById($simulator->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($simulator->id()))->toBeTrue()
        ->and($found?->deviceIdentifier()->equals($simulator->deviceIdentifier()))->toBeTrue()
        ->and($found?->softwareVersion())->toBe('1.0.0')
        ->and($found?->location())->toBeNull()
        ->and($found?->status())->toBe(SimulatorStatus::Active)
        ->and($found?->integrationKey()->hash())->toBe($simulator->integrationKey()->hash())
        ->and($found?->history())->toBe([]);
});

it('guarda y recupera la ubicacion y el historial', function (): void {
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.2.0',
        location: 'Sede Cartago',
        integrationKey: IntegrationKey::generate(),
    );
    $simulator->suspend('Mantenimiento', new DateTimeImmutable('2026-08-27T00:00:00+00:00'));
    app(SimulatorRepository::class)->save($simulator);

    $found = app(SimulatorRepository::class)->findById($simulator->id());

    expect($found?->location())->toBe('Sede Cartago')
        ->and($found?->status())->toBe(SimulatorStatus::Suspended)
        ->and($found?->history())->toHaveCount(1)
        ->and($found?->history()[0]->reason)->toBe('Mantenimiento');
});

it('encuentra un simulador por identificador de dispositivo', function (): void {
    $simulator = newPersistableSimulator('SIM-UNICO-001');
    app(SimulatorRepository::class)->save($simulator);

    $found = app(SimulatorRepository::class)->findByDeviceIdentifier(DeviceIdentifier::fromString('SIM-UNICO-001'));

    expect($found?->id()->equals($simulator->id()))->toBeTrue();
    expect(app(SimulatorRepository::class)->findByDeviceIdentifier(DeviceIdentifier::fromString('SIM-INEXISTENTE')))->toBeNull();
});

it('lista todos los simuladores registrados', function (): void {
    $repository = app(SimulatorRepository::class);
    $repository->save(newPersistableSimulator());
    $repository->save(newPersistableSimulator());
    $repository->save(newPersistableSimulator());

    expect($repository->all())->toHaveCount(3);
});

it('reemplaza el historial en vez de duplicarlo al guardar de nuevo', function (): void {
    $repository = app(SimulatorRepository::class);
    $simulator = newPersistableSimulator();
    $simulator->suspend(null, new DateTimeImmutable('now'));
    $repository->save($simulator);
    $repository->save($simulator);

    $found = $repository->findById($simulator->id());

    expect($found?->history())->toHaveCount(1);
});

it('borra en cascada el historial al eliminar el simulador', function (): void {
    $repository = app(SimulatorRepository::class);
    $simulator = newPersistableSimulator();
    $simulator->suspend(null, new DateTimeImmutable('now'));
    $repository->save($simulator);

    SimulatorModel::query()->where('id', $simulator->id()->value())->delete();

    expect(SimulatorHistoryEntryModel::query()->where('simulator_id', $simulator->id()->value())->count())->toBe(0);
});
